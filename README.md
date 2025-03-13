# Content Audit Plugin

## Description
The Content Audit plugin helps you track and manage content review dates for your WordPress pages. It provides a systematic way to ensure content remains up-to-date by scheduling regular reviews and notifying stakeholders when content needs to be reviewed.

## Features
- **Content Review Dashboard**: View all pages that need review, sorted by review date
- **Email Notifications**: Automatically send emails to stakeholders when content needs review
- **Content Review Form**: Allow stakeholders to submit content reviews through a simple form
- **Submissions Tracking**: Track all content review submissions in the admin dashboard
- **Filtering Options**: Filter content by review date (overdue, next 30 days, 3 months, 6 months, etc.)

## Installation
1. Upload the `content-audit` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'Content Audit' menu item in your WordPress admin

## Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher

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

### Viewing Submissions
1. Go to the WordPress admin dashboard
2. Click on 'Content Audit' > 'Submissions'
3. You'll see a table of all content review submissions
4. Click on a submission to view details

## Advanced Configuration

### Email Templates
The plugin uses HTML email templates that can be customized through WordPress filters:
- `content_audit_email_template` - Filter for the main notification email template
- `content_audit_submission_email_template` - Filter for the submission confirmation email

### URL Handling
The plugin automatically handles URLs to ensure:
- Content page links in emails and the dashboard point to the live website (https://www.pepper.money)
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
For support, please contact the Pepper Money UX Team.

## Credits
Developed by Pepper Money
