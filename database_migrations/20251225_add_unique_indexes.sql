-- Migration: Add unique indexes to prevent duplicate usernames/emails and boat numbers

-- Add unique index on users.username
ALTER TABLE users
ADD UNIQUE INDEX uq_users_username (username);

-- Add unique index on users.email
ALTER TABLE users
ADD UNIQUE INDEX uq_users_email (email);

-- Add unique index on boats.boat_number
ALTER TABLE boats
ADD UNIQUE INDEX uq_boats_boat_number (boat_number);

-- Note: Run these commands carefully if existing duplicates exist. Remove or clean duplicates before applying.
