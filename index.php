<?php

require_once 'conf/db.php';

create_db();
create_tables();

header('Location: view.php');