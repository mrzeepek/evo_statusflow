# EvoStatusFlow - PrestaShop Order Status Management Module

## Overview

EvoStatusFlow is a professional PrestaShop module designed to automate order status transitions in your e-commerce workflow. This module allows store administrators to create rules that automatically move orders from one status to another based on customizable conditions and time delays.

## Features

- **Rule-Based Status Management**: Define transitions from one PrestaShop order status to another
- **Time-Delayed Transitions**: Set specific time intervals before status changes are applied
- **Conditional Processing**: Filter orders using SQL conditions for precise targeting
- **Manual and Automated Execution**: Run rules on-demand or automatically via cron jobs
- **Comprehensive Logging**: Track all status changes with detailed history
- **User-Friendly Interface**: Manage all rules from an intuitive back-office interface

## Technical Requirements

- PrestaShop 8.0 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Access to cron jobs for automatic execution (optional)

## Installation

1. Download the latest version of the module
2. Upload the `evo_statusflow` folder to your PrestaShop `/modules` directory
3. Navigate to the Modules section in your PrestaShop back-office
4. Find "EvoStatusFlow" in the module list and click "Install"
5. Configure the module settings according to your needs

## Configuration

### General Settings

1. Go to **Modules > Module Manager > EvoStatusFlow > Configure**
2. Configure the following settings:
  - **Cron Frequency**: How often the status flow processor should run
  - **Batch Size**: Maximum number of orders to process in a single run
  - **Enable Detailed Logging**: Toggle detailed logging of status changes
  - **Notification Email**: Optional email for important status change notifications

### Creating Status Flow Rules

1. Navigate to **EvoStatusFlow > Rules**
2. Click "Add New Rule"
3. Configure the rule:
  - **From Status**: The starting order status
  - **To Status**: The destination order status
  - **Delay (hours)**: Time to wait before applying the transition
  - **SQL Condition**: Optional SQL WHERE clause to filter orders
  - **Auto-Execute**: Enable for automated processing via cron
  - **Active**: Toggle to enable/disable the rule

## Automated Processing Setup

To enable automatic status transitions, set up a cron job on your server:

```
# Run every hour (adjust timing based on your configuration)
0 * * * * php /path/to/your/prestashop/bin/console evolutive:evo_statusflow:process
```

For testing or specific rule execution, you can add parameters:

```
# Process only a specific rule
php /path/to/your/prestashop/bin/console evolutive:evo_statusflow:process --rule-id=5

# Test without making actual changes
php /path/to/your/prestashop/bin/console evolutive:evo_statusflow:process --dry-run
```

## Usage Examples

### Example 1: Automatic Order Fulfillment

Create a rule to automatically change orders from "Payment Accepted" to "Shipped" after 48 hours:

- **From Status**: Payment Accepted
- **To Status**: Shipped
- **Delay (hours)**: 48
- **SQL Condition**: `total_paid <= 100` (only for orders under 100€)

### Example 2: Abandoned Cart Management

Mark orders as "Canceled" if they've been in "Awaiting Payment" status for too long:

- **From Status**: Awaiting Payment
- **To Status**: Canceled
- **Delay (hours)**: 72
- **Auto-Execute**: Enabled

## Troubleshooting

- **Rules not executing**: Check that the rule is marked as "Active" and "Auto-Execute"
- **Cron not working**: Verify your server's cron configuration and logs
- **SQL conditions not filtering correctly**: Test your SQL conditions directly on the orders table

## Support

For technical support, feature requests, or bug reports, please contact us at:

- Email: support@evolutive-group.com
- Website: https://evolutive-group.com

## License

EvoStatusFlow is proprietary software. Unauthorized copying, distributing, or modifying this module is strictly prohibited.

Copyright © 2025 Evolutive Group. All rights reserved.
