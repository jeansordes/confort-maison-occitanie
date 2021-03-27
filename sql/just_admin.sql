use :cmo_db_name;

-- admin account (admin_cmo@yopmail.com:admin)
select new_user('admin', 'admin_cmo@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'Administrateur', null, null, null, null, null, null, null, null);

select 'Query done';