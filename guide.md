Build a full-stack Car Listing Web Application (Stage 1: Foundation System) for a car dealership.

TECH STACK:
- Frontend: HTML, CSS and JS
- Backend: PHP
- Database: MySQL

CORE GOAL:
Create a professional car listing platform where an admin can add, edit, and delete cars, and users can view available cars and contact via WhatsApp.

FEATURES:

1. PUBLIC WEBSITE (FRONTEND)
- Homepage with featured cars
- Car listing page (grid layout like Jiji)
- Car details page (images, price, specs, description)
- Search/filter (brand, price range, model, year)
- WhatsApp “Contact Seller” button on each car (click opens WhatsApp chat with prefilled message including car name + price)

2. ADMIN DASHBOARD
- Secure login page
- Add new car (title, price, images, description, specs)
- Edit / delete cars
- Mark car as “sold” or “available”
- View all listings in table format

Admin Schema:
- username: vaautosales
- password (hashed): vaautosales123

4. WHATSAPP INTEGRATION
- Each car should have a WhatsApp button:
  https://wa.me/234XXXXXXXXXX?text=I'm interested in [Car Name] priced at [Price]

5. UI/UX REQUIREMENTS
- Clean, modern, mobile-first design with cars animations 
- Card-based car layout
- Fast loading
- Professional dealership feel
- Similar inspiration: Jiji / Autotrader
- Robost SEO

6. STRUCTURE
- /pages or /app (depending on Next.js version)
- /components
- /models
- /lib (db connection, utils)
- /admin dashboard route
- /api routes for CRUD

7. EXTRA (IMPORTANT)
- Make it scalable for future Stage 2 automation (lead tracking + WhatsApp automation)
- Keep code clean and modular
- Add comments where necessary

8. Folder 
- Backend 
- Frontend

DELIVERABLE:
A fully working Stage 1 system that can be run locally and deployed immediately.