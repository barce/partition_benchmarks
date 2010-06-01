use cbtester;
drop table meta_table;
CREATE TABLE meta_table (
   id INT NOT NULL primary key AUTO_INCREMENT ,
   tablename varchar(255),
         iterator int,
         last_user_id int
);

insert into meta_table (tablename, iterator) values ('users_00', 4);
