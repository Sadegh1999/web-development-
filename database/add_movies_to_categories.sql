-- Add more movies
INSERT INTO movies (title, description, poster_url, price, release_year, duration, rating) VALUES
('The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 'shawshank.jpg', 9.99, 1994, 142, 9.3),
('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control to his son, who expands the family business.', 'godfather.jpg', 12.99, 1972, 175, 9.2),
('The Lord of the Rings: The Fellowship of the Ring', 'A meek Hobbit from the Shire and eight companions set out on a journey to destroy the powerful One Ring.', 'lotr.jpg', 14.99, 2001, 178, 8.8),
('Goodfellas', 'The story of Henry Hill and his life in the mob, covering his relationship with his wife Karen Hill and his mob partners Jimmy Conway and Tommy DeVito.', 'goodfellas.jpg', 11.99, 1990, 146, 8.7),
('The Silence of the Lambs', 'A young F.B.I. cadet must receive the help of an incarcerated and manipulative cannibal killer to help catch another serial killer.', 'silence_lambs.jpg', 10.99, 1991, 118, 8.6),
('The Avengers', 'Earth''s mightiest heroes must come together and learn to fight as a team if they are going to stop the mischievous Loki and his alien army from enslaving humanity.', 'avengers.jpg', 13.99, 2012, 143, 8.0),
('The Hangover', 'Three friends wake up from a bachelor party in Las Vegas, with no memory of the previous night and the bachelor missing.', 'hangover.jpg', 9.99, 2009, 100, 7.7),
('The Exorcist', 'When a teenage girl is possessed by a mysterious entity, her mother seek the help of two priests to save her daughter.', 'exorcist.jpg', 10.99, 1973, 122, 8.0),
('Toy Story', 'A cowboy doll is profoundly threatened and jealous when a new spaceman figure supplants him as top toy in a boy''s room.', 'toy_story.jpg', 9.99, 1995, 81, 8.3),
('The Princess Bride', 'While home sick in bed, a young boy''s grandfather reads him the story of a farmboy-turned-pirate who encounters numerous obstacles.', 'princess_bride.jpg', 8.99, 1987, 98, 8.1);

-- Associate existing movies with categories
-- Inception (Action, Sci-Fi, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 1, id FROM categories WHERE name IN ('Action', 'Sci-Fi', 'Thriller');

-- The Dark Knight (Action, Crime, Drama, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 2, id FROM categories WHERE name IN ('Action', 'Crime', 'Drama', 'Thriller');

-- Pulp Fiction (Crime, Drama, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 3, id FROM categories WHERE name IN ('Crime', 'Drama', 'Thriller');

-- The Matrix (Action, Sci-Fi, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 4, id FROM categories WHERE name IN ('Action', 'Sci-Fi', 'Thriller');

-- Forrest Gump (Drama, Romance)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 5, id FROM categories WHERE name IN ('Drama', 'Romance');

-- Associate new movies with categories
-- The Shawshank Redemption (Drama)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 6, id FROM categories WHERE name = 'Drama';

-- The Godfather (Crime, Drama)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 7, id FROM categories WHERE name IN ('Crime', 'Drama');

-- The Lord of the Rings (Adventure, Fantasy)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 8, id FROM categories WHERE name IN ('Adventure', 'Fantasy');

-- Goodfellas (Crime, Drama)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 9, id FROM categories WHERE name IN ('Crime', 'Drama');

-- The Silence of the Lambs (Crime, Drama, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 10, id FROM categories WHERE name IN ('Crime', 'Drama', 'Thriller');

-- The Avengers (Action, Adventure, Sci-Fi)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 11, id FROM categories WHERE name IN ('Action', 'Adventure', 'Sci-Fi');

-- The Hangover (Comedy)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 12, id FROM categories WHERE name = 'Comedy';

-- The Exorcist (Horror, Thriller)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 13, id FROM categories WHERE name IN ('Horror', 'Thriller');

-- Toy Story (Animation, Adventure, Comedy)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 14, id FROM categories WHERE name IN ('Animation', 'Adventure', 'Comedy');

-- The Princess Bride (Adventure, Comedy, Fantasy, Romance)
INSERT INTO movie_categories (movie_id, category_id) 
SELECT 15, id FROM categories WHERE name IN ('Adventure', 'Comedy', 'Fantasy', 'Romance'); 