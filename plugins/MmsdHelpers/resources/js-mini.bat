@echo off

java -jar closure-compiler\closure-compiler.jar --js ..\webroot\js\address-search.js --js_output_file ..\webroot\js\address-search.min.js
