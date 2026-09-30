INSERT INTO users (username, email, password, role, status) VALUES 
('admin1', 'admin1@internlink.com', '$2y$10$UWL.pIqyXkGDNcq2VE50X.CY8BHxE6Q06P37MlqFUm2IS3X0x02yq', 'admin', 'active'),
('admin2', 'admin2@internlink.com', '$2y$10$UWL.pIqyXkGDNcq2VE50X.CY8BHxE6Q06P37MlqFUm2IS3X0x02yq', 'admin', 'active');

INSERT INTO admins (user_id, full_name) 
SELECT user_id, username as full_name
FROM users 
WHERE role = 'admin' 
AND user_id NOT IN (SELECT user_id FROM admins);

