# Complete Task List for Ensurex Testing and Implementation

## 1. Quote Management Testing

- [X] Test creating a new quote with all policy types (Health, Dental, Vision, Life)
- [X] Verify all quote form fields work properly
- [X] Test adding multiple applicants to a quote
- [X] Test searching and filtering quotes
- [X] Test updating quote status
- [X] Test adding notes to quotes
- [X] **Missing Feature**: Add a "Compact View" button to quotes for Health Sherpa data entry
- [X] Bug: Required field error message is in English
- [X] Todo: Convert to Policy in View Mode
- [X] Bug: Convert to Policy should only appear in View Mode
- [X] Bug: Phone field should have verifications
- [X] Bug: H/S H/E S S Año should be integers
- [X] Bug: Contact updated message should only show if there are changes in the contact data
- [X] Bug: Search by contact name not working
- [X] Bug: Table filters are distruptive with Estatus Filters. Should have just the types that I need and group by utility.
- [X] Bug: When adding a new note, a blank line is first.
- [X] Todo: View Mode in same tab a change status button
- [ ] Todo: Button to change quote status
- [ ] Todo: Message that shows if Income is lower to what is required by Kynect

## 2. Quote-to-Policy Conversion Testing

- [X] Test converting quotes to policies using the existing button
- [X] Don't show convert to policy button when the quote status is converted
- [X] Verify all quote data is properly transferred to policies
- [X] Add policy number to Policy Form
- [X] Check correct policy number generation
- [X] Verify policy starts in "Draft" status
- [X] Test quote status changes to "Converted" after conversion

## 3. Policy Management Testing

- [X] Test viewing and editing existing policies
- [X] Test all policy tabs (contact details, applicants, payments, etc.)
- [X] Finalizar Tab shouldn't be visible after finally creating policies
- [ ] Create a View that shows all the relevante information about the policy
- [ ] When editing a Policy, after clicking save it should return to the Policies page
- [ ] Status should have a button in view and edit mode to change it requiring an observation
- [ ] Test policy status workflow (Draft → Created → Active)
- [ ] Test document upload and verification
- [ ] Test searching and filtering policies
- [ ] **Missing Feature**: Add ability to duplicate a policy with a different type (e.g., Health to Life)

## 4. Document Management Testing

- [ ] Test uploading documents to policies
- [ ] Test document status changes (Pending → Approved)
- [ ] Test document expiration date tracking
- [ ] Test document requirement tracking

## 5. Missing Features Implementation

- [ ] Create compact view for quotes (for Health Sherpa data entry)
- [ ] Add policy duplication functionality
- [ ] **Missing Feature**: Add commission tracking fields to policies
  - Commission status (Paid/Unpaid)
  - Commission payment date
  - Commission recipient
- [ ] **Missing Feature**: Create commission report generation system
  - Report by date range
  - Option to exclude specific policies
  - Mark reports as paid
  - Store historical commission reports

## 6. Policy Status and Commission System Testing

- [ ] Test policy status changes through complete workflow
- [ ] Implement and test commission tracking fields
- [ ] Test commission report generation
- [ ] Test excluding policies from commission
- [ ] Test marking commissions as paid

## 7. User Permissions and Roles Testing

- [ ] Test different user role permissions (administrator, supervisor, assistant)
- [ ] Verify appropriate access restrictions
- [ ] Document any missing permission controls

## 8. CSV Import Testing

- [ ] Test existing CSV import functionality
- [ ] Implement any needed changes for new data format
- [ ] Test error handling for invalid data

## 9. Final End-to-End Testing

- [ ] Test complete workflow from quote creation to policy activation to commission payment
- [ ] Test edge cases and error handling
- [ ] Document any remaining bugs or issues

## 10. Documentation

- [ ] Document all new features
- [ ] Update user guides if needed
- [ ] Create training materials for users
