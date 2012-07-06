#!/bin/bash
php parser.php
/etc/init.d/sphinxsearch stop
indexer centerGoods
/etc/init.d/sphinxsearch start
php search.php