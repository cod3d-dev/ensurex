# Complete Task List for Ensurex Testing and Implementation

## 1. Quote Management Testing

-   [x] Test creating a new quote with all policy types (Health, Dental, Vision, Life)
-   [x] Verify all quote form fields work properly
-   [x] Test adding multiple applicants to a quote
-   [x] Test searching and filtering quotes
-   [x] Test updating quote status
-   [x] Test adding notes to quotes
-   [ ] **Missing Feature**: Add a "Compact View" button to quotes for Health Sherpa data entry
-   [ ] Bug: Required field error message is in English
-   [ ] Bug: Phone field should have verifications
-   [ ] Bug: H/S H/E S S Año should be integers
-   [ ] Bug: Contact updated message should only show if there are changes in the contact data
-   [x] Bug: Search by contact name not working
-   [ ] Bug: Table filters are distruptive with Estatus Filters. Should have just the types that I need and group by utility.
-   [ ] Bug: When adding a new note, a blank line is first.
-   [ ] Todo: Message should show if Income is lower to what is required by Kynect
-   [ ] Todo: Compact view with a change status button

## 2. Quote-to-Policy Conversion Testing

-   [ ] Test converting quotes to policies using the existing button
-   [ ] Verify all quote data is properly transferred to policies
-   [ ] Check correct policy number generation
-   [ ] Verify policy starts in "Draft" status
-   [ ] Test quote status changes to "Converted" after conversion

## 3. Policy Management Testing

-   [ ] Test viewing and editing existing policies
-   [ ] Test all policy tabs (contact details, applicants, payments, etc.)
-   [ ] Test policy status workflow (Draft → Created → Active)
-   [ ] Test document upload and verification
-   [ ] Test searching and filtering policies
-   [ ] **Missing Feature**: Add ability to duplicate a policy with a different type (e.g., Health to Life)

## 4. Document Management Testing

-   [ ] Test uploading documents to policies
-   [ ] Test document status changes (Pending → Approved)
-   [ ] Test document expiration date tracking
-   [ ] Test document requirement tracking

## 5. Missing Features Implementation

-   [ ] Create compact view for quotes (for Health Sherpa data entry)
-   [ ] Add policy duplication functionality
-   [ ] **Missing Feature**: Add commission tracking fields to policies
    -   Commission status (Paid/Unpaid)
    -   Commission payment date
    -   Commission recipient
-   [ ] **Missing Feature**: Create commission report generation system
    -   Report by date range
    -   Option to exclude specific policies
    -   Mark reports as paid
    -   Store historical commission reports

## 6. Policy Status and Commission System Testing

-   [ ] Test policy status changes through complete workflow
-   [ ] Implement and test commission tracking fields
-   [ ] Test commission report generation
-   [ ] Test excluding policies from commission
-   [ ] Test marking commissions as paid

## 7. User Permissions and Roles Testing

-   [ ] Test different user role permissions (administrator, supervisor, assistant)
-   [ ] Verify appropriate access restrictions
-   [ ] Document any missing permission controls

## 8. CSV Import Testing

-   [ ] Test existing CSV import functionality
-   [ ] Implement any needed changes for new data format
-   [ ] Test error handling for invalid data

## 9. Final End-to-End Testing

-   [ ] Test complete workflow from quote creation to policy activation to commission payment
-   [ ] Test edge cases and error handling
-   [ ] Document any remaining bugs or issues

## 10. Documentation

-   [ ] Document all new features
-   [ ] Update user guides if needed
-   [ ] Create training materials for users
