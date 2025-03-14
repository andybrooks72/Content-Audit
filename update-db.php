<?php
/**
 * Database Update Script
 *
 * This script updates the content_audit_submissions table to use content_id and content_title
 * instead of page_id and page_title, allowing the plugin to work with both posts and pages.
 *
 * @package ContentAudit
 */

// Bootstrap WordPress.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php';

// Check if user is logged in and has appropriate capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have sufficient permissions to access this page.' );
}

/**
 * Update the database structure.
 *
 * @return void
 */
function content_audit_update_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'content_audit_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if table exists.
    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like( $table_name )
        )
    ) !== null;

    if ( $table_exists ) {
        echo '<p>Content Audit submissions table exists.</p>';

        // Check if the content_id column exists.
        $content_id_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'content_id'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        
        if ( empty( $content_id_exists ) ) {
            echo '<p>Updating table structure to support both posts and pages...</p>';
            
            // Create a temporary table with the new structure.
            $temp_table_name = $table_name . '_temp';
            
            // Drop the temporary table if it exists.
            $wpdb->query( "DROP TABLE IF EXISTS $temp_table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            
            // Create the temporary table with the new structure.
            $sql = "CREATE TABLE $temp_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                content_id bigint(20) NOT NULL,
                content_title varchar(255) NOT NULL,
                content_type varchar(20) NOT NULL DEFAULT 'page',
                stakeholder_name varchar(100) NOT NULL,
                stakeholder_email varchar(100) NOT NULL,
                stakeholder_department varchar(100) NOT NULL,
                submission_date datetime NOT NULL,
                needs_changes tinyint(1) NOT NULL DEFAULT 0,
                support_ticket_url varchar(255) DEFAULT '',
                next_review_date varchar(20) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            // Check if page_id and page_title columns exist in the original table.
            $page_id_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'page_id'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $page_title_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'page_title'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            
            if ( ! empty( $page_id_exists ) && ! empty( $page_title_exists ) ) {
                // Copy data from the old table to the new one.
                $result = $wpdb->query( "INSERT INTO $temp_table_name (id, content_id, content_title, content_type, stakeholder_name, stakeholder_email, stakeholder_department, submission_date, needs_changes, support_ticket_url, next_review_date) 
                                SELECT id, page_id, page_title, content_type, stakeholder_name, stakeholder_email, stakeholder_department, submission_date, needs_changes, support_ticket_url, next_review_date 
                                FROM $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                
                if ( $result !== false ) {
                    echo '<p>Data migrated successfully.</p>';
                    
                    // Drop the old table.
                    $wpdb->query( "DROP TABLE $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    
                    // Rename the temporary table to the original name.
                    $wpdb->query( "RENAME TABLE $temp_table_name TO $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    
                    echo '<p>Table structure updated successfully.</p>';
                    
                    // Update the database version.
                    update_option( 'content_audit_db_version', '1.0.1' );
                    echo '<p>Database version updated to 1.0.1.</p>';
                } else {
                    echo '<p>Error migrating data. Please check the database manually.</p>';
                }
            } else {
                echo '<p>Original table structure does not have expected columns. Manual intervention required.</p>';
            }
        } else {
            echo '<p>Table structure is already up to date.</p>';
        }
    } else {
        echo '<p>Content Audit submissions table does not exist. Creating new table...</p>';
        
        // Create the submissions table with the new structure.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            content_id bigint(20) NOT NULL,
            content_title varchar(255) NOT NULL,
            content_type varchar(20) NOT NULL DEFAULT 'page',
            stakeholder_name varchar(100) NOT NULL,
            stakeholder_email varchar(100) NOT NULL,
            stakeholder_department varchar(100) NOT NULL,
            submission_date datetime NOT NULL,
            needs_changes tinyint(1) NOT NULL DEFAULT 0,
            support_ticket_url varchar(255) DEFAULT '',
            next_review_date varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        
        // Check if the table was created successfully.
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
            echo '<p>Table created successfully.</p>';
            
            // Update the database version.
            update_option( 'content_audit_db_version', '1.0.1' );
            echo '<p>Database version updated to 1.0.1.</p>';
        } else {
            echo '<p>Error creating table. Please check the database manually.</p>';
        }
    }
}

// Run the update function.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Content Audit Database Update</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f0f0f1;
        }
        .wrap {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        h1 {
            color: #23282d;
            font-size: 23px;
            font-weight: 400;
            margin: 0 0 20px;
            padding: 9px 0 4px;
            line-height: 1.3;
        }
        p {
            margin: 1em 0;
            line-height: 1.5;
        }
        .success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .error {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 2.15384615;
            min-height: 30px;
            margin: 0;
            padding: 0 10px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            -webkit-appearance: none;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
        }
        .button:hover {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Content Audit Database Update</h1>
        <p>This script updates the database structure for the Content Audit plugin to support both posts and pages.</p>
        
        <div class="results">
            <?php content_audit_update_database(); ?>
        </div>
        
        <p class="success">Database update completed. You can now close this page and return to your WordPress dashboard.</p>
        
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=content-audit' ) ); ?>" class="button">Return to Content Audit</a></p>
    </div>
</body>
</html>
