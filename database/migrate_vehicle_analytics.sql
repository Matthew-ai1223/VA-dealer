-- VA Auto Sales - Vehicle click tracking (interest button)
USE va_aut_sales;

ALTER TABLE lead_activities
  MODIFY COLUMN activity_type ENUM(
    'vehicle_viewed',
    'vehicle_inquiry',
    'contact_request',
    'inspection_request',
    'callback_request',
    'whatsapp_click',
    'interest_click'
  ) NOT NULL;
