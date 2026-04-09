-- Supabase PostgreSQL Schema for ITicketHub
-- Generated for PostgreSQL compatibility

-- 1. Master List (Employees)
CREATE TABLE "lrn_master_list" (
    "biometricsid" VARCHAR(50) PRIMARY KEY,
    "employeeid" VARCHAR(50),
    "firstname" VARCHAR(100),
    "lastname" VARCHAR(100),
    "middlename" VARCHAR(100),
    "department" VARCHAR(100)
);

-- 2. OJT Employees
CREATE TABLE "app_ojt_employees" (
    "employee_id" VARCHAR(50) PRIMARY KEY,
    "full_name" VARCHAR(255)
);

-- 3. Ticket Roles
CREATE TABLE "it_ticket_roles" (
    "empcode" VARCHAR(50) PRIMARY KEY,
    "ticket_role" VARCHAR(100),
    "isactive" INTEGER DEFAULT 1
);

-- 4. Ticket Categories
CREATE TABLE "it_ticket_categ" (
    "id" SERIAL PRIMARY KEY,
    "category_name" VARCHAR(100) NOT NULL
);

-- 5. Ticket Requests
CREATE TABLE "it_ticket_request" (
    "id" SERIAL PRIMARY KEY,
    "status" VARCHAR(50) DEFAULT 'Open',
    "date_created" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "requestor" VARCHAR(50),
    "department" VARCHAR(100),
    "subject" TEXT,
    "description" TEXT,
    "date_updated" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "categ_name" VARCHAR(100),
    "assigned_to" TEXT,
    "assignedby" VARCHAR(50),
    "assignedby_dt" TIMESTAMP,
    "urgency_level" VARCHAR(50),
    "reject_by" VARCHAR(50),
    "reject_dt" TIMESTAMP,
    "reject_remarks" TEXT,
    "completed_by" VARCHAR(50),
    "completed_by_dt" TIMESTAMP
);

-- 6. History Logs
CREATE TABLE "it_ticket_history_logs" (
    "id" SERIAL PRIMARY KEY,
    "ticket_id" INTEGER,
    "ticket_user" VARCHAR(50),
    "user_fullname" VARCHAR(255),
    "action" TEXT,
    "status" VARCHAR(50),
    "remarks" TEXT,
    "date_time" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Attachments
CREATE TABLE "it_ticket_attachments" (
    "id" SERIAL PRIMARY KEY,
    "ticketid" INTEGER REFERENCES "it_ticket_request"("id"),
    "filepath" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Stateless Sessions (NEW)
CREATE TABLE "it_app_sessions" (
    "id" VARCHAR(255) PRIMARY KEY,
    "data" TEXT,
    "last_accessed" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Survey Questions
CREATE TABLE "survey_questions" (
    "id" SERIAL PRIMARY KEY,
    "question_text" TEXT NOT NULL,
    "is_active" INTEGER DEFAULT 1,
    "sort_order" INTEGER DEFAULT 0
);

-- 10. Ticket Surveys
CREATE TABLE "ticket_surveys" (
    "id" SERIAL PRIMARY KEY,
    "ticket_id" INTEGER REFERENCES "it_ticket_request"("id"),
    "requestor_empcode" VARCHAR(50),
    "q1" TEXT, "q2" TEXT, "q3" TEXT, "q4" TEXT, "q5" TEXT,
    "q6" TEXT, "q7" TEXT, "q8" TEXT, "q9" TEXT, "q10" TEXT,
    "comments" TEXT,
    "submitted_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MOCK DATA GENERATION --

-- Mock Master List
INSERT INTO "lrn_master_list" ("biometricsid", "employeeid", "firstname", "lastname", "department") VALUES
('1001', 'EMP001', 'Admin', 'User', 'IT Department'),
('1002', 'EMP002', 'John', 'Doe', 'Marketing'),
('1003', 'EMP003', 'Jane', 'Smith', 'Finance'),
('1004', 'EMP004', 'Mark', 'Wilson', 'Human Resources');

-- Mock OJT Employees
INSERT INTO "app_ojt_employees" ("employee_id", "full_name") VALUES
('OJT001', 'OJT Trainee One'),
('OJT002', 'OJT Trainee Two');

-- Mock Roles
INSERT INTO "it_ticket_roles" ("empcode", "ticket_role", "isactive") VALUES
('1001', 'it_admin', 1),
('1002', 'user', 1),
('1003', 'user', 1),
('1004', 'it_pic', 1);

-- Mock Categories
INSERT INTO "it_ticket_categ" ("category_name") VALUES
('Hardware Issue'),
('Software Installation'),
('Network Connection'),
('Account Access'),
('Printer Problems');

-- Mock Survey Questions
INSERT INTO "survey_questions" ("question_text", "is_active", "sort_order") VALUES
('How satisfied are you with the resolution time?', 1, 1),
('Was the technician professional and helpful?', 1, 2),
('Did the solution resolve your issue completely?', 1, 3);

-- Mock Tickets
INSERT INTO "it_ticket_request" ("status", "requestor", "department", "subject", "description", "urgency_level", "categ_name") VALUES
('Open', '1002', 'Marketing', 'Monitor not working', 'The second monitor is black and wont turn on.', 'Medium', 'Hardware Issue'),
('Assigned', '1003', 'Finance', 'SAP Login Issue', 'Cannot login to SAP production environment.', 'High', 'Account Access'),
('Closed', 'OJT001', 'IT Department', 'Install VS Code', 'Requesting installation of VS Code for development.', 'Low', 'Software Installation');

-- Mock History
INSERT INTO "it_ticket_history_logs" ("ticket_id", "ticket_user", "user_fullname", "action", "status") VALUES
(1, '1002', 'John Doe', 'Ticket Created', 'Open'),
(2, '1003', 'Jane Smith', 'Ticket Created', 'Open'),
(2, '1001', 'Admin User', 'Assigned ticket to: Mark Wilson', 'Assigned');
