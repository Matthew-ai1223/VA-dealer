PROJECT: Car Dealership Lead Management & Buyer Tracking System (Stage 2)

Build Stage 2 of an existing Car Dealership Platform.

Current Status:

* Car listing website completed
* Admin dashboard completed
* Vehicle inventory management completed
* WhatsApp integration completed

Goal:
Create a Lead Management and Buyer Tracking System that captures, organizes, and tracks all potential buyers from the website.

TECH STACK:

* Existing TECH STACK system

========================================
LEAD MANAGEMENT SYSTEM
======================

Create a Lead model:

Lead:

* id
* fullName
* phoneNumber
* email
* interestedVehicle
* budget
* source

  * Website
  * Nairaland
  * Instagram
  * Facebook
  * WhatsApp
* status

  * New Lead
  * Contacted
  * Interested
  * Negotiating
  * Closed Won
  * Closed Lost
* notes
* assignedTo
* createdAt
* updatedAt

========================================
WEBSITE LEAD CAPTURE
====================

On every vehicle details page:

Add:

* Request Information Form
* Book Inspection Form
* Request Call Back Form

When submitted:

* Save lead to database
* Notify admin dashboard
* Show success message

========================================
ADMIN CRM DASHBOARD
===================

Create a CRM section.

Features:

1. Leads Overview

* Total Leads
* New Leads
* Interested Leads
* Negotiating Leads
* Closed Sales

2. Leads Table
   Columns:

* Name
* Phone
* Vehicle
* Source
* Status
* Date

3. Lead Details Page
   Display:

* Customer information
* Vehicle interest
* Notes
* Communication history

4. Update Lead Status
   Admin can change:
   New Lead → Contacted → Interested → Negotiating → Closed

5. Internal Notes
   Admin can add notes for each lead.

========================================
BUYER TRACKING
==============

Track:

* Vehicle viewed
* Vehicle inquiry
* Contact request
* Inspection request

Store all activities.

========================================
LEAD SOURCE ANALYTICS
=====================

Create dashboard widgets:

* Leads from Nairaland
* Leads from Instagram
* Leads from Facebook
* Leads from Website
* Leads from WhatsApp

Display:

* Count
* Conversion rate

========================================
WHATSAPP LEAD INTEGRATION
=========================

When user clicks WhatsApp:

Automatically:

* Create lead record
* Save vehicle name
* Save timestamp
* Save source

Track:

* Number of WhatsApp inquiries
* Most requested vehicles

========================================
EMAIL NOTIFICATIONS
===================

When new lead arrives:

Send email notification to admin.

Email contains:

* Customer Name
* Phone Number
* Vehicle
* Source

========================================
UI REQUIREMENTS
===============

Build a professional CRM-style interface.

Inspiration:

* HubSpot
* Salesforce
* Zoho CRM

Features:

* Responsive
* Modern cards
* Charts
* Search
* Filters
* Pagination

========================================
DATABASE OPTIMIZATION
=====================

Create indexes for:

* phoneNumber
* source
* status
* createdAt

========================================
CODE QUALITY
============

* Use PHP with modular MVC-style folders (`models/`, `lib/`, `api/`, `admin/`)
* Reusable includes and components
* API validation
* Error handling
* Production ready

DELIVERABLE:
A complete Lead Capture, CRM, Buyer Tracking, Analytics, and Inquiry Management System integrated into the existing car dealership platform.
