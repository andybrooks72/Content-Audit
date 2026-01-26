# Content Audit Plugin

## Description
The Content Audit plugin helps you track and manage content review dates for your WordPress pages. It provides a systematic way to ensure content remains up-to-date by scheduling regular reviews and notifying stakeholders when content needs to be reviewed.

## Features
- **Content Review Dashboard**: View all pages that need review, sorted by review date
- **Email Notifications**: Automatically send emails to stakeholders when content needs review
- **Content Review Form**: Allow stakeholders to submit content reviews through a simple form
- **Submissions Tracking**: Track all content review submissions in the admin dashboard
- **Filtering Options**: Filter content by review date (overdue, next 30 days, 3 months, 6 months, etc.)
- **Customizable Settings**: Tabbed settings interface for easy configuration
- **Form Customization**: Customize button and link colors for the review form
- **Email Customization**: Upload custom header images and customize email appearance
- **Theme Override Support**: Email templates can be overridden in your theme directory
- **Dynamic Post Type Support**: Automatically adapts labels for Posts, Pages, and custom post types

## Installation
1. Upload the `content-audit` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'Content Audit' menu item in your WordPress admin

## Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- Advanced Custom Fields (ACF) Pro plugin
- StoutLogic ACF Builder (included in the plugin)

## Usage

### Setting Up Content for Review
1. Edit any WordPress page
2. Scroll down to the 'Content Audit' meta box
3. Fill in the following fields:
   - **Stakeholder Name**: The person responsible for reviewing the content
   - **Stakeholder Email**: Email address for notifications
   - **Stakeholder Department**: Department or team of the stakeholder
   - **Last Review Date**: When the content was last reviewed
   - **Next Review Date**: When the content should be reviewed next
4. Save the page

### Viewing Content That Needs Review
1. Go to the WordPress admin dashboard
2. Click on 'Content Audit' in the main menu
3. You'll see a table of all pages that need review, sorted by review date
4. Use the filter dropdown to view content by different time periods:
   - Next 30 Days & Overdue (default)
   - Overdue Only
   - Next 3 Months
   - Next 6 Months
   - Next 12 Months
   - All Pages

### Sending Review Notifications
1. From the Content Audit dashboard, find the page that needs review
2. Click the 'Send Email' button next to the page
3. An email will be sent to the stakeholder with:
   - A link to the page that needs review
   - The deadline for review
   - A link to the content review form

### Content Review Form
The plugin provides a shortcode `[content_audit_form]` that can be placed on any page to display the content review form. When a stakeholder receives an email notification, they'll be directed to this form.

The form includes:
- Page information (automatically populated)
- Stakeholder information (automatically populated)
- Options to indicate if content changes are needed
- Support ticket URL field (if changes are needed)
- Next review date selection

### Plugin Settings
The plugin includes a comprehensive settings page accessible from WordPress Admin > Content Audit > Settings. The settings are organized into three tabs:

#### Plugin Settings Tab
- **General Settings**: Configure basic plugin behavior
- **Display Settings**: Customize how content audit information is displayed
  - Show/hide admin columns
  - Set base URL for live site links
  - Configure support ticket URL
- **Post Types Settings**: Select which post types should have content audit fields

#### Form Settings Tab
- **Success Message**: Customize the message shown after form submission
- **Button Background Color**: Choose the background color for form buttons
- **Button Text Color**: Choose the text color for form buttons
- **Link Text Color**: Choose the color for links in the form

#### Email Settings Tab
- **Notification Email**: Email address that receives submission notifications
- **From Email**: Email address used in the "From" field of emails
- **From Name**: Name displayed in emails (replaces "Pepper Money UX Team")
- **Email Header Image**: Upload a custom header image (GIF, PNG, JPEG, max 150KB) for email templates. If no image is uploaded, the site title and tagline will be displayed instead.

### Viewing Submissions
1. Go to the WordPress admin dashboard
2. Click on 'Content Audit' > 'Submissions'
3. You'll see a table of all content review submissions
4. Click on a submission to view details

## Advanced Configuration

### Adding Support for Additional Post Types

The plugin uses StoutLogic ACF Builder to create and manage the custom fields. By default, the plugin adds the audit fields to Pages and Posts, but you can easily extend it to support other post types.

To add support for additional post types:

1. Open the file `includes/class-acfauditfields.php`
2. Locate the `audit_fields` method
3. Find the `setLocation` method call and add your custom post type:

```php
$audit_fields->setLocation( 'post_type', '==', 'page' )
    ->or( 'post_type', '==', 'post' )
    ->or( 'post_type', '==', 'your_custom_post_type' ); // Add your custom post type here
```

4. Save the file and the audit fields will now appear on your custom post type edit screens

### Using StoutLogic ACF Builder

This plugin uses the StoutLogic ACF Builder library to programmatically create ACF field groups. This approach offers several advantages:

- Field definitions are version-controlled
- Fields can be easily modified through code
- No need to manually create fields through the ACF interface
- Consistent field creation across different environments

The ACF Builder is initialized in the `class-acfauditfields.php` file. To modify existing fields or add new ones:

1. Locate the `audit_fields` method in `class-acfauditfields.php`
2. Use the ACF Builder methods to add or modify fields:

```php
$audit_fields->addText(
    'field_name',
    array(
        'label' => __( 'Field Label', 'ContentAudit' ),
        // Add other field settings here
    )
);
```

For more information on using ACF Builder, refer to the [StoutLogic ACF Builder documentation](https://github.com/StoutLogic/acf-builder).

### Email Templates
The plugin uses HTML email templates that can be customized in two ways:

#### Theme Override
You can override email templates by copying them to your theme directory:
- Copy `templates/email/submission-notification.php` to `your-theme/pm-content-audit/email/submission-notification.php`
- Copy `templates/email/backend-submission-notification.php` to `your-theme/pm-content-audit/email/backend-submission-notification.php`

This allows you to fully customize the email appearance while maintaining updates to the plugin.

#### Available Templates
- **submission-notification.php**: Email sent to admins when a stakeholder submits a content review
- **backend-submission-notification.php**: Email sent to stakeholders when admins trigger a review notification

Both templates support the following customizable elements:
- Header image (or site title/tagline fallback)
- Button colors (from Form Settings)
- Link colors (from Form Settings)
- From name (from Email Settings)

#### WordPress Filters
The plugin also provides WordPress filters for advanced customization:
- `content_audit_email_template` - Filter for the main notification email template
- `content_audit_submission_email_template` - Filter for the submission confirmation email

### Dynamic Post Type Labels
The plugin automatically detects and uses appropriate labels for different post types:
- Standard WordPress posts display as "Post"
- Pages display as "Page"
- Custom post types use their registered singular label

This ensures consistent and accurate terminology throughout the plugin interface and email notifications.

### URL Handling
The plugin automatically handles URLs to ensure:
- Content page links in emails and the dashboard point to the live website (configured in Display Settings)
- Form submission links and admin links use the local site URL

### Database
The plugin creates a custom database table `wp_content_audit_submissions` to store all form submissions.

## Troubleshooting

### Emails Not Being Sent
- Check that your WordPress site can send emails
- Verify the stakeholder email addresses are correct
- Check your server's email configuration

### Form Not Displaying
- Make sure you've added the `[content_audit_form]` shortcode to a page
- Check that the page is published and accessible

### Missing Content in Dashboard
- Ensure pages have the required Content Audit meta fields filled in
- Check that the Next Review Date is set correctly

## Support
For support, please contact Andy Brooks.

## Credits
Developed by Andy Brooks
