use cbtester;
CREATE TABLE meta_table (
   id INT NOT NULL primary key AUTO_INCREMENT ,
   tablename varchar(255),
         iterator int(10),
         last_user_id int(10)
);

insert into meta_table (tablename, iterator) values ('users_00', 4);
