<?php
/**
 * WP-CLI command to migrate beapi/key-figure content.
 *
 * @package Blockparty\Key_Figure
 */

namespace Blockparty\Key_Figure\Cli;

use Blockparty\Key_Figure\Migration\KeyFigureBlockMigrator;
use WP_CLI;
use WP_CLI_Command;

/**
 * Blockparty Key Figure WP-CLI commands.
 */
class MigrateFromKeyFigureBlockCommand extends WP_CLI_Command {

	/**
	 * Option holding the block widgets content.
	 *
	 * @var string
	 */
	const WIDGETS_OPTION = 'widget_block';

	/**
	 * Whether the content is saved.
	 *
	 * @var bool
	 */
	private bool $live = false;

	/**
	 * Whether the markup is aligned with the current block output.
	 *
	 * @var bool
	 */
	private bool $modernize = true;

	/**
	 * Whether post revisions are migrated too.
	 *
	 * @var bool
	 */
	private bool $include_revisions = true;

	/**
	 * How many posts are read per batch.
	 *
	 * @var int
	 */
	private int $batch_size = 100;

	/**
	 * Post types to migrate, empty for all of them.
	 *
	 * @var string[]
	 */
	private array $post_types = [];

	/**
	 * How many posts were read.
	 *
	 * @var int
	 */
	private int $posts_scanned = 0;

	/**
	 * How many posts were migrated.
	 *
	 * @var int
	 */
	private int $posts_updated = 0;

	/**
	 * How many block widgets were migrated.
	 *
	 * @var int
	 */
	private int $widgets_updated = 0;

	/**
	 * Content migrator.
	 *
	 * @var KeyFigureBlockMigrator
	 */
	private KeyFigureBlockMigrator $migrator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->migrator = new KeyFigureBlockMigrator();
	}

	/**
	 * Migrate beapi/key-figure content to blockparty/key-figure.
	 *
	 * Rewrites the block name (`wp:beapi/key-figure`) and the BEM root class
	 * (`wp-block-beapi-key-figure`) in post content and in block widgets. Unless
	 * `--no-modernize` is used, the saved markup is also aligned with the current
	 * block output so migrated blocks stay valid in the editor.
	 *
	 * Runs as a dry-run unless `--live` is passed.
	 *
	 * ## OPTIONS
	 *
	 * [--live]
	 * : Save the migrated content. Without this flag nothing is written to the database.
	 *
	 * [--[no-]modernize]
	 * : Align the markup with the current block output: `p` key wrapper and number data attributes. Default: true.
	 *
	 * [--skip-revisions]
	 * : Leave post revisions untouched.
	 *
	 * [--blog_id=<id>]
	 * : Only migrate this site of the network. Default: every site.
	 *
	 * [--post-type=<post-types>]
	 * : Comma-separated list of post types to migrate. Default: every post type.
	 *
	 * [--posts-per-page=<number>]
	 * : How many posts are read per batch. Default: 100.
	 *
	 * ## EXAMPLES
	 *
	 *     # Report what would change, on every site.
	 *     wp blockparty key-figure migrate
	 *
	 *     # Migrate every site of the network.
	 *     wp blockparty key-figure migrate --live
	 *
	 *     # Migrate a single site, revisions excluded.
	 *     wp blockparty key-figure migrate --live --blog_id=2 --skip-revisions
	 *
	 *     # Only rename the block and its CSS classes.
	 *     wp blockparty key-figure migrate --live --no-modernize
	 *
	 * @param string[]                  $args       Positional arguments.
	 * @param array<string, bool|string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ): void {
		$this->live              = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'live', false );
		$this->modernize         = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'modernize', true );
		$this->include_revisions = ! (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-revisions', false );
		$this->batch_size        = max( 1, (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'posts-per-page', 100 ) );
		$this->post_types        = $this->resolve_post_types( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'post-type', '' ) );

		$sites = $this->resolve_sites( $assoc_args );

		if ( ! $this->live ) {
			WP_CLI::warning( 'Dry run: nothing is saved. Add --live to apply the migration.' );
		}

		foreach ( $sites as $site_id ) {
			if ( is_multisite() ) {
				switch_to_blog( $site_id );
			}

			WP_CLI::log(
				sprintf(
					'Site %1$d (%2$s) — post types: %3$s, revisions: %4$s, modernize: %5$s',
					$site_id,
					home_url( '/' ),
					empty( $this->post_types ) ? 'all' : implode( ', ', $this->post_types ),
					$this->include_revisions ? 'yes' : 'no',
					$this->modernize ? 'yes' : 'no'
				)
			);

			$this->migrate_posts();
			$this->migrate_widgets();

			if ( is_multisite() ) {
				restore_current_blog();
			}
		}

		WP_CLI::success(
			sprintf(
				'Done. Sites: %1$d, posts scanned: %2$d, posts %3$s: %4$d, widgets %3$s: %5$d, blocks renamed: %6$d, markup modernized: %7$d, markup skipped: %8$d.',
				count( $sites ),
				$this->posts_scanned,
				$this->live ? 'updated' : 'to update',
				$this->posts_updated,
				$this->widgets_updated,
				$this->migrator->renamed,
				$this->migrator->modernized,
				$this->migrator->skipped
			)
		);

		if ( $this->migrator->skipped > 0 ) {
			WP_CLI::warning( 'Some blocks were renamed without markup modernization because no number could be read from their markup. Check them in the editor.' );
		}
	}

	/**
	 * Migrate the post content of the current site.
	 *
	 * @return void
	 */
	private function migrate_posts(): void {
		$after_id = 0;

		while ( true ) {
			$posts = $this->fetch_posts( $after_id );
			$read  = count( $posts );

			foreach ( $posts as $post ) {
				$after_id = (int) $post->ID;
				++$this->posts_scanned;

				$content = $this->migrator->migrate_content( (string) $post->post_content, $this->modernize );

				if ( null === $content ) {
					continue;
				}

				++$this->posts_updated;

				WP_CLI::log(
					sprintf(
						'%1$s post %2$d (%3$s)',
						$this->live ? '[update]' : '[dry-run]',
						(int) $post->ID,
						(string) $post->post_type
					)
				);

				if ( $this->live ) {
					$this->save_post( $post, $content );
				}
			}

			if ( $read < $this->batch_size ) {
				return;
			}
		}
	}

	/**
	 * Migrate the block widgets of the current site.
	 *
	 * @return void
	 */
	private function migrate_widgets(): void {
		$widgets = get_option( self::WIDGETS_OPTION );

		if ( ! is_array( $widgets ) ) {
			return;
		}

		$updated = 0;

		foreach ( $widgets as $key => $widget ) {
			if ( ! is_array( $widget ) || ! isset( $widget['content'] ) || ! is_string( $widget['content'] ) ) {
				continue;
			}

			$content = $this->migrator->migrate_content( $widget['content'], $this->modernize );

			if ( null === $content ) {
				continue;
			}

			$widget['content'] = $content;
			$widgets[ $key ]   = $widget;
			++$updated;
			++$this->widgets_updated;

			WP_CLI::log(
				sprintf(
					'%1$s block widget %2$s',
					$this->live ? '[update]' : '[dry-run]',
					(string) $key
				)
			);
		}

		if ( $updated > 0 && $this->live ) {
			update_option( self::WIDGETS_OPTION, $widgets );
		}
	}

	/**
	 * Read the next batch of posts holding legacy markup.
	 *
	 * @param int $after_id Only read posts with a greater ID.
	 * @return object[]
	 */
	private function fetch_posts( int $after_id ): array {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$sql = "SELECT ID, post_type, post_content FROM {$wpdb->posts} WHERE ID > %d AND ( post_content LIKE %s OR post_content LIKE %s )";

		$values = [
			$after_id,
			'%' . $wpdb->esc_like( 'wp:' . KeyFigureBlockMigrator::LEGACY_BLOCK_NAME ) . '%',
			'%' . $wpdb->esc_like( KeyFigureBlockMigrator::LEGACY_CLASS_ROOT ) . '%',
		];

		if ( ! $this->include_revisions ) {
			$sql .= " AND post_type != 'revision'";
		}

		if ( ! empty( $this->post_types ) ) {
			$sql   .= ' AND post_type IN ( ' . implode( ', ', array_fill( 0, count( $this->post_types ), '%s' ) ) . ' )';
			$values = array_merge( $values, $this->post_types );
		}

		$sql     .= ' ORDER BY ID ASC LIMIT %d';
		$values[] = $this->batch_size;

		/** @var object[] $posts */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$posts = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return $posts;
	}

	/**
	 * Save the migrated content of a single post.
	 *
	 * @param object $post    Row read from the posts table.
	 * @param string $content Migrated content.
	 * @return void
	 */
	private function save_post( object $post, string $content ): void {
		/** @var \wpdb $wpdb */
		global $wpdb;

		// Revisions are content snapshots: writing them through wp_update_post()
		// would run the whole insert pipeline (status transition, hooks) on them.
		if ( 'revision' === $post->post_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->posts, [ 'post_content' => $content ], [ 'ID' => (int) $post->ID ] );
			clean_post_cache( (int) $post->ID );

			return;
		}

		// wp_update_post() expects slashed data; without wp_slash(), block comment
		// escapes like \u002d (for "--") become bare "u002d".
		$updated = wp_update_post(
			[
				'ID'           => (int) $post->ID,
				'post_content' => wp_slash( $content ),
			],
			true
		);

		if ( is_wp_error( $updated ) ) {
			WP_CLI::warning(
				sprintf(
					'Failed to update post %1$d: %2$s',
					(int) $post->ID,
					$updated->get_error_message()
				)
			);
		}
	}

	/**
	 * Resolve the sites to migrate.
	 *
	 * @param array<string, bool|string> $assoc_args Associative arguments.
	 * @return int[]
	 */
	private function resolve_sites( array $assoc_args ): array {
		$blog_id = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'blog_id', 0 );

		if ( ! is_multisite() ) {
			if ( $blog_id > 0 && get_current_blog_id() !== $blog_id ) {
				WP_CLI::error( 'The --blog_id flag requires a multisite installation.' );
			}

			return [ get_current_blog_id() ];
		}

		if ( $blog_id > 0 ) {
			if ( null === get_site( $blog_id ) ) {
				WP_CLI::error( sprintf( 'Site %d does not exist.', $blog_id ) );
			}

			return [ $blog_id ];
		}

		return array_map(
			'intval',
			get_sites(
				[
					'fields' => 'ids',
					'number' => 0,
				]
			)
		);
	}

	/**
	 * Resolve the post types to migrate.
	 *
	 * @param string $raw Comma-separated post types from the CLI.
	 * @return string[] Empty when every post type is migrated.
	 */
	private function resolve_post_types( string $raw ): array {
		if ( '' === trim( $raw ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
	}
}
