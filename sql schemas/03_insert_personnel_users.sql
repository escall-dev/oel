-- Migration script to insert personnel into users table
-- Default password for all users is: qpteo
-- Default role for all users is: viewer

INSERT IGNORE INTO `users` (`username`, `full_name`, `password`, `role`) VALUES
('iking', 'Ferdinand L. Rellorosa, CEPS', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('diane', 'Diane G. Francisco, PDO IV', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('kristel', 'Marie Kristel B. Corpin, SEPS', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('cristy', 'Cristy A. Mendoza, PDO III', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('vj', 'Vernie Glojun T. Lasmarias, PDO III', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('dave', 'Lester Dave G. Pua, PDO III', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('chris', 'Christopher E. Siscar, PDO I', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('jillian', 'Clarence Jillian Villena, EPS I', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('venus', 'Venus Mae D. Cabuñalda, ADAS I', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer'),
('alex', 'Alexander Joerenz E. Escallente', '$2y$10$ISmhEpXdI9PvM6xh3NUWOe8LGOQ0Crmfewx5BW1xa.4WoIvVnZ0HS', 'viewer');
