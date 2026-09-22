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
	 * Whether the content is reported without being saved.
	 *
	 * @var bool
	 */
	private bool $dry_run = false;

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
	 * Always targets a single site. On multisite, use the global `--url`
	 * parameter to pick the site.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report changes without updating the database.
	 *
	 * [--[no-]modernize]
	 * : Align the markup with the current block output: `p` key wrapper and number data attributes. Default: true.
	 *
	 * [--skip-revisions]
	 * : Leave post revisions untouched.
	 *
	 * [--post-type=<post-types>]
	 * : Comma-separated list of post types to migrate. Default: every post type.
	 *
	 * [--posts-per-page=<number>]
	 * : How many posts are read per batch. Default: 100.
	 *
	 * ## EXAMPLES
	 *
	 *     # Apply the migration.
	 *     wp blockparty key-figure migrate
	 *
	 *     # Report what would change.
	 *     wp blockparty key-figure migrate --dry-run
	 *
	 *     # Migrate one site of a network, revisions excluded.
	 *     wp blockparty key-figure migrate --skip-revisions --url=example.com/site-2
	 *
	 *     # Only rename the block and its CSS classes.
	 *     wp blockparty key-figure migrate --no-modernize
	 *
	 * @param string[]                   $args       Positional arguments.
	 * @param array<string, bool|string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ): void {
		$this->dry_run           = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$this->modernize         = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'modernize', true );
		$this->include_revisions = ! (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-revisions', false );
		$this->batch_size        = max( 1, (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'posts-per-page', 100 ) );
		$this->post_types        = $this->resolve_post_types( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'post-type', '' ) );

		WP_CLI::log(
			sprintf(
				'Migrating site %1$d (%2$s)%3$s — post types: %4$s, revisions: %5$s, modernize: %6$s',
				get_current_blog_id(),
				home_url( '/' ),
				$this->dry_run ? ' [dry-run]' : '',
				empty( $this->post_types ) ? 'all' : implode( ', ', $this->post_types ),
				$this->include_revisions ? 'yes' : 'no',
				$this->modernize ? 'yes' : 'no'
			)
		);

		$this->migrate_posts();
		$this->migrate_widgets();

		$this->print_summary();

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
						$this->dry_run ? '[dry-run]' : '[update]',
						(int) $post->ID,
						(string) $post->post_type
					)
				);

				if ( ! $this->dry_run ) {
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
					$this->dry_run ? '[dry-run]' : '[update]',
					(string) $key
				)
			);
		}

		if ( $updated > 0 && ! $this->dry_run ) {
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

	/**
	 * Print migration counters as a CLI table.
	 *
	 * @return void
	 */
	private function print_summary(): void {
		$updated_label = $this->dry_run ? 'would update' : 'updated';

		WP_CLI::success( 'Done.' );

		WP_CLI\Utils\format_items(
			'table',
			[
				[
					'metric' => 'Posts scanned',
					'count'  => $this->posts_scanned,
				],
				[
					'metric' => 'Posts ' . $updated_label,
					'count'  => $this->posts_updated,
				],
				[
					'metric' => 'Widgets ' . $updated_label,
					'count'  => $this->widgets_updated,
				],
				[
					'metric' => 'Blocks renamed',
					'count'  => $this->migrator->renamed,
				],
				[
					'metric' => 'Markup modernized',
					'count'  => $this->migrator->modernized,
				],
				[
					'metric' => 'Markup skipped',
					'count'  => $this->migrator->skipped,
				],
			],
			[ 'metric', 'count' ]
		);
	}
}
