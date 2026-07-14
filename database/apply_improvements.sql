-- 1. Resolve duplicate patient email addresses before adding UNIQUE constraint
UPDATE hs_patient SET patient_email = 'sapnnna988+17@gmail.com' WHERE patient_id = 17;
UPDATE hs_patient SET patient_email = 'sapnnna988+18@gmail.com' WHERE patient_id = 18;

-- 2. Correct orphaned records to prevent foreign key errors
UPDATE hs_doctor SET sub_category_id = 13 WHERE doctor_id = 6;
UPDATE hs_appointment SET sub_category_id = 13 WHERE appointment_id IN (10, 11, 12, 13, 14, 15, 17);
UPDATE hs_appointment SET category_id = 2 WHERE appointment_id = 11;

-- 3. Convert patient_dob in hs_patient from INT to DATE safely
ALTER TABLE hs_patient ADD COLUMN patient_dob_new DATE NULL;
UPDATE hs_patient SET patient_dob_new = STR_TO_DATE(CONCAT(CASE WHEN patient_dob BETWEEN 1900 AND 2100 THEN patient_dob ELSE 1990 END, '-01-01'), '%Y-%m-%d');
ALTER TABLE hs_patient DROP COLUMN patient_dob;
ALTER TABLE hs_patient CHANGE COLUMN patient_dob_new patient_dob DATE NOT NULL;

-- 4. Convert doctor_dob and doctor_doj in hs_doctor from varchar(20) to DATE
ALTER TABLE hs_doctor MODIFY COLUMN doctor_dob DATE NOT NULL;
ALTER TABLE hs_doctor MODIFY COLUMN doctor_doj DATE NOT NULL;

-- 5. Convert appointment_time in hs_appointment from TIME(5) to standard TIME
ALTER TABLE hs_appointment MODIFY COLUMN appointment_time TIME NOT NULL;

-- 6. Add UNIQUE constraints to email columns
ALTER TABLE hs_admin ADD CONSTRAINT unique_admin_email UNIQUE (admin_email);
ALTER TABLE hs_doctor ADD CONSTRAINT unique_doctor_email UNIQUE (doctor_email);
ALTER TABLE hs_patient ADD CONSTRAINT unique_patient_email UNIQUE (patient_email);

-- 7. Add index for scheduling optimization
ALTER TABLE hs_appointment ADD INDEX idx_appt_date (appointment_date);

-- 8. Fix ON UPDATE behavior for creation date/time audit fields
ALTER TABLE hs_admin MODIFY COLUMN created_data timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_appointment MODIFY COLUMN created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_category MODIFY COLUMN created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_contact MODIFY COLUMN created_data datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_doctor MODIFY COLUMN created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_service MODIFY COLUMN created_data timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_sub_category MODIFY COLUMN created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE hs_patient MODIFY COLUMN created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 9. Add Foreign Key constraints
ALTER TABLE hs_admin ADD CONSTRAINT fk_admin_role FOREIGN KEY (role_id) REFERENCES hs_role (role_id) ON DELETE RESTRICT;
ALTER TABLE hs_sub_category ADD CONSTRAINT fk_subcat_category FOREIGN KEY (category_id) REFERENCES hs_category (category_id) ON DELETE CASCADE;

ALTER TABLE hs_service ADD CONSTRAINT fk_service_category FOREIGN KEY (category_id) REFERENCES hs_category (category_id) ON DELETE CASCADE;
ALTER TABLE hs_service ADD CONSTRAINT fk_service_sub_category FOREIGN KEY (sub_category_id) REFERENCES hs_sub_category (sub_category_id) ON DELETE CASCADE;

ALTER TABLE hs_doctor ADD CONSTRAINT fk_doc_role FOREIGN KEY (role_id) REFERENCES hs_role (role_id) ON DELETE RESTRICT;
ALTER TABLE hs_doctor ADD CONSTRAINT fk_doc_category FOREIGN KEY (category_id) REFERENCES hs_category (category_id) ON DELETE CASCADE;
ALTER TABLE hs_doctor ADD CONSTRAINT fk_doc_sub_category FOREIGN KEY (sub_category_id) REFERENCES hs_sub_category (sub_category_id) ON DELETE CASCADE;
ALTER TABLE hs_doctor ADD CONSTRAINT fk_doc_service FOREIGN KEY (service_id) REFERENCES hs_service (service_id) ON DELETE CASCADE;

ALTER TABLE hs_appointment ADD CONSTRAINT fk_appt_sub_category FOREIGN KEY (sub_category_id) REFERENCES hs_sub_category (sub_category_id) ON DELETE CASCADE;
ALTER TABLE hs_appointment ADD CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES hs_service (service_id) ON DELETE CASCADE;

ALTER TABLE hs_ai_chats ADD CONSTRAINT fk_chat_patient FOREIGN KEY (patient_id) REFERENCES hs_patient (patient_id) ON DELETE CASCADE;
