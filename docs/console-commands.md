# Ensurex Console Commands

This document provides detailed information about the available Artisan console commands for the Ensurex application. These commands help with quote to policy conversion, policy auto-completion, and generating test data using factories.

## Table of Contents

- [Quote to Policy Conversion Commands](#quote-to-policy-conversion-commands)
  - [Convert Single Quote](#convert-single-quote)
  - [Convert and Auto-Complete Single Quote](#convert-and-auto-complete-single-quote)
  - [Convert Batch of Quotes](#convert-batch-of-quotes)
- [Policy Management Commands](#policy-management-commands)
  - [Change Policy Status](#change-policy-status)
- [Factory Data Generation Commands](#factory-data-generation-commands)
  - [Create Quotes](#create-quotes)
  - [Create Issues](#create-issues)
  - [Create Policy Documents](#create-policy-documents)

## Quote to Policy Conversion Commands

### Convert Single Quote

**Command**: `quotes:convert {quote_id} [--date=YYYY-MM-DD]`

Converts a single quote to a policy, optionally setting the creation date of the policy.

**Arguments**:
- `quote_id`: ID of the quote to convert (required)

**Options**:
- `--date`: Optional creation date for the policy (format: YYYY-MM-DD)

**Examples**:
```bash
# Convert without specifying date (uses current date)
php artisan quotes:convert 5

# Convert and set a specific creation date
php artisan quotes:convert 5 --date=2025-07-15
```

**Output Example**:
```
Converting quote ID: 5...
✅ Successfully converted to policy ID: 7
+----------+-----------+-------------+---------------------+----------------+
| Quote ID | Policy ID | Policy Type | Effective Date      | Premium Amount |
+----------+-----------+-------------+---------------------+----------------+
| 5        | 7         | health      | 2025-01-01 00:00:00 | $0.00          |
+----------+-----------+-------------+---------------------+----------------+
```

### Convert and Auto-Complete Single Quote

**Command**: `quotes:convert-auto {quote_id} [--date=YYYY-MM-DD]`

Converts a single quote to a policy and then automatically completes it with random data. Optionally sets the creation date of the policy.

**Arguments**:
- `quote_id`: ID of the quote to convert and auto-complete (required)

**Options**:
- `--date`: Optional creation date for the policy (format: YYYY-MM-DD)

**Examples**:
```bash
# Convert and auto-complete without specifying date
php artisan quotes:convert-auto 36

# Convert and auto-complete with a specific creation date
php artisan quotes:convert-auto 36 --date=2025-07-15
```

**Output Example**:
```
Converting quote #36 to a policy...
✅ Quote converted successfully to policy #8
Auto-completing policy with random data...
✅ Policy #8 auto-completed successfully!

📋 Policy Details:
+-------------------+-----------------------+
| Field             | Value                 |
+-------------------+-----------------------+
| ID                | 8                     |
| Code              | D00003                |
| Policy Type       | dental                |
| Status            | created               |
| Insurance Company | Regence Blue Cross    |
| Inscription Type  | sep_change_of_address |
| Plan              | Silver                |
| Premium Amount    | $0.00                 |
| Contact           | Lcda. Nerea Nájera    |
+-------------------+-----------------------+
```

### Convert Batch of Quotes

**Command**: `quotes:convert-batch {count=5} {--options}`

Converts a batch of random quotes to policies.

**Arguments**:
- `count`: Number of quotes to convert (default: 5)

**Options**:
- `--status=pending`: Status of quotes to convert (pending/accepted) (default: pending)
- `--type=`: Filter by policy type (health, dental, etc.)

**Quote Filter Options**:
- `--quote-date=`: Filter quotes by specific date (YYYY-MM-DD)
- `--quote-date-range=`: Filter quotes by date range (last_week|last_month|this_month|this_year)
- `--quote-date-start=`: Start date for filtering quotes (YYYY-MM-DD)
- `--quote-date-end=`: End date for filtering quotes (YYYY-MM-DD)

**Policy Date Options**:
- `--date=`: Set specific policy creation date (YYYY-MM-DD)
- `--date-range=`: Set policy dates within range (last_week|last_month|this_month|this_year)
- `--date-start=`: Start date for policy date range (YYYY-MM-DD)
- `--date-end=`: End date for policy date range (YYYY-MM-DD)
- `--random-dates`: When used with date range, randomize dates instead of evenly spacing

**Execution Options**:
- `--dry-run`: Show what would be converted without making changes
- `--auto-complete`: Automatically complete the policies with random data

**Examples**:
```bash
# Convert 5 pending quotes to policies
php artisan quotes:convert-batch 5

# Convert 10 accepted quotes to policies and auto-complete them
php artisan quotes:convert-batch 10 --status=accepted --auto-complete

# Show what would happen if you converted 3 quotes with health policy type
php artisan quotes:convert-batch 3 --type=health --dry-run

# Filter quotes from last month (based on quote date)
php artisan quotes:convert-batch 5 --quote-date-range=last_month

# Create policies with creation dates in last month
php artisan quotes:convert-batch 5 --date-range=last_month

# Filter health quotes by date range and create policies with random dates in a custom range
php artisan quotes:convert-batch 3 --type=health --quote-date-range=this_month --date-start=2023-01-01 --date-end=2023-01-31 --random-dates

# Convert quotes from a specific date and set the policy creation date to last week
php artisan quotes:convert-batch 5 --quote-date=2023-05-15 --date-range=last_week
```

**Output Example**:
```
Found 5 quotes to convert
Converting Quote #2... ✅ Success: Policy #2
Converting Quote #50... ✅ Success: Policy #3
Converting Quote #6... ✅ Success: Policy #4
Converting Quote #11... ✅ Success: Policy #5
Converting Quote #23... ✅ Success: Policy #6

📋 Conversion Summary:
+----------+-----------+-------------+---------------------+---------+
| Quote ID | Policy ID | Policy Type | Contact             | Premium |
+----------+-----------+-------------+---------------------+---------+
| 2        | 2         | health      | Antonio Rodrígez    | $0.00   |
| 50       | 3         | accident    | Patricia Gaona Hijo | $0.00   |
| 6        | 4         | accident    | Angela Vigil Hijo   | $0.00   |
| 11       | 5         | dental      | Sofia Ulloa         | $0.00   |
| 23       | 6         | vision      | Julia Marín Hijo    | $0.00   |
+----------+-----------+-------------+---------------------+---------+

📊 Results Summary:
  ✓ Successfully converted: 5
  ✗ Failed conversions: 0
```

## Policy Management Commands

### Change Policy Status

**Command**: `policies:change-status [options]`

Change the status of a specific policy or a batch of policies, with options to filter by current status and date range.

**Options**:
- `--id=`: ID of a specific policy to change status
- `--count=`: Number of policies to change in batch mode
- `--from=`: Current status to filter policies by (comma-separated for multiple)
- `--to=`: Target status to set (random if not provided)
- `--start-date=`: Start date for filtering policies (format: YYYY-MM-DD)
- `--end-date=`: End date for filtering policies (format: YYYY-MM-DD)
- `--activation-date=`: Specific activation date for active policies (format: YYYY-MM-DD)
- `--activation-date-range=`: Predefined date range for activation date (this_week|last_week|this_month|last_month|this_year)
- `--use-quote-date`: Use the quote creation date as the activation date

**Examples**:
```bash
# Change a specific policy to active status
php artisan policies:change-status --id=15 --to=active

# Change 10 draft policies to random statuses
php artisan policies:change-status --count=10 --from=draft

# Change all policies created in July 2025 from draft to active
php artisan policies:change-status --from=draft --to=active --start-date=2025-07-01 --end-date=2025-07-31

# Change 5 policies from any status to pending
php artisan policies:change-status --count=5 --to=pending

# Change all rejected policies from the last month to active
php artisan policies:change-status --from=rejected --to=active --start-date=2025-07-01 --end-date=2025-07-31

# Change policies to active with a specific activation date
php artisan policies:change-status --from=draft --to=active --activation-date=2025-08-01

# Change policies to active with activation dates from this month
php artisan policies:change-status --from=draft --to=active --activation-date-range=this_month

# Change policies to active using their quote creation date as activation date
php artisan policies:change-status --from=draft --to=active --use-quote-date
```

**Output Example**:
```
This will change 5 policies with status [draft] to 'active'. Continue? (yes/no) [no]:
> yes

[============================] 100%

✅ Successfully updated status for 5 policies.
```

## Factory Data Generation Commands

### Create Quotes

**Command**: `factory:quotes {count=5} {--options}`

Creates quotes using the Quote factory.

**Arguments**:
- `count`: Number of quotes to create (default: 5)

**Options**:
- `--status=`: Set quote status (pending/sent/accepted/rejected/converted)
- `--policy_types=`: Comma-separated policy types (health,dental,vision,accident,life)
- `--contact_id=`: Use a specific contact ID
- `--user_id=`: Use a specific user ID
- `--date=`: Set specific quote date (YYYY-MM-DD)
- `--date-range=`: Use a predefined date range (last_week/last_month/this_month/this_year)
- `--date-start=`: Start date for custom range (YYYY-MM-DD)
- `--date-end=`: End date for custom range (YYYY-MM-DD)
- `--random-dates`: Randomize dates within the selected range
- `--applicants=1`: Number of applicants for each quote
- `--show`: Show detailed information about created quotes
- `--summary-only`: Only show summary, suppress progress output

**Examples**:
```bash
# Create 3 pending quotes with health and dental policy types
php artisan factory:quotes 3 --status=pending --policy_types=health,dental

# Create 1 quote with a specific date and show detailed info
php artisan factory:quotes 1 --date=2025-10-15 --show

# Create 20 quotes quickly with minimal output
php artisan factory:quotes 20 --summary-only

# Create 5 quotes with 3 applicants each
php artisan factory:quotes 5 --applicants=3

# Create 10 quotes with dates from last month
php artisan factory:quotes 10 --date-range=last_month

# Create 5 quotes with random dates within this year
php artisan factory:quotes 5 --date-range=this_year --random-dates

# Create 3 quotes within a custom date range
php artisan factory:quotes 3 --date-start=2025-01-01 --date-end=2025-03-31
```

**Output Example**:
```
Creating 2 quotes...
✅ Created Quote #54 with status: pending
✅ Created Quote #55 with status: pending

📋 Quote Summary:
+----+---------+----------------+----------------------+------------+------------+
| ID | Status  | Policy Types   | Contact              | Date       | Applicants |
+----+---------+----------------+----------------------+------------+------------+
| 54 | pending | health, dental | Dra. Alicia Elizondo | 2025-07-29 | 3          |
| 55 | pending | health, dental | Sofia Pizarro        | 2025-07-12 | 3          |
+----+---------+----------------+----------------------+------------+------------+
```

### Create Issues

**Command**: `factory:issues {count=5} {--options}`

Creates issues using the Issue factory.

**Arguments**:
- `count`: Number of issues to create (default: 5)

**Options**:
- `--policy_id=`: Attach issues to a specific policy ID
- `--quote_id=`: Attach issues to a specific quote ID
- `--issue_type=`: Create issues of a specific type ID
- `--random`: Attach issues to random policies or quotes

**Examples**:
```bash
# Create 3 standalone issues
php artisan factory:issues 3

# Create 5 issues for policy with ID 10
php artisan factory:issues 5 --policy_id=10

# Create 2 issues of type 1 for a specific quote
php artisan factory:issues 2 --quote_id=15 --issue_type=1

# Create 10 issues and attach them randomly to policies or quotes
php artisan factory:issues 10 --random
```

**Output Example**:
```
Creating 2 standalone issues...
✅ Created 2 standalone issues.
+----+---------+---------------------------------------------+-----------+----------+
| ID | Type    | Description                                 | Policy ID | Quote ID |
+----+---------+---------------------------------------------+-----------+----------+
| 6  | Unknown | Le hicieron un cobro adicional que no co... | 8         | N/A      |
| 7  | Unknown | La aseguradora realizó un cobro no auto...  | 10        | N/A      |
+----+---------+---------------------------------------------+-----------+----------+
```

### Create Policy Documents

**Command**: `factory:policy-documents {count=5} {--options}`

Creates policy documents using the PolicyDocument factory.

**Arguments**:
- `count`: Number of documents to create (default: 5)

**Options**:
- `--policy_id=`: Attach documents to a specific policy ID
- `--status=`: Set document status (draft/pending/approved/rejected)
- `--expired`: Create expired documents
- `--due_today`: Create documents due today
- `--due_next_week`: Create documents due next week
- `--random`: Attach documents to random policies

**Examples**:
```bash
# Create 5 pending documents
php artisan factory:policy-documents 5 --status=pending

# Create 3 expired documents for a specific policy
php artisan factory:policy-documents 3 --expired --policy_id=7

# Create 10 documents due next week for random policies
php artisan factory:policy-documents 10 --due_next_week --random
```

**Output Example**:
```
Creating 2 documents for Policy #1...
✅ Created 2 documents for Policy #1.
+----+---------------+---------+------------+-----------+
| ID | Name          | Status  | Due Date   | Policy ID |
+----+---------------+---------+------------+-----------+
| 31 | Documento est | pending | 2025-08-16 | 1         |
| 32 | Documento et  | pending | 2025-08-08 | 1         |
+----+---------------+---------+------------+-----------+
```

## Tips & Best Practices

1. **Auto-Completion**: When using `--auto-complete` option with batch conversion, ensure you have sufficient insurance companies in the database.

2. **Dry Run Mode**: Always use the `--dry-run` option first when converting a large number of quotes to see what would be affected.

3. **Factory Data**: Factory commands generate data with realistic Spanish names and information, ideal for testing the application with production-like data.

4. **Multiple Policy Types**: When a quote has multiple policy types, converting it will create a separate policy for each type.

5. **Performance**: For large batch operations, consider using smaller batches to avoid memory issues.

## Common Issues and Troubleshooting

- **Quote Already Converted**: If a quote is already converted, the conversion command will display a warning and skip it.

- **Missing Related Data**: Ensure that the necessary related data (contacts, users, insurance companies) exists in the database before running these commands.

- **Issue Type Not Found**: When specifying an issue type ID that doesn't exist, the command will show you a list of available issue types.
