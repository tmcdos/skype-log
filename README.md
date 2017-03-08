# Skype logger

### Monitoring tool to log Skype chat messages to remote web service

This application consists of a Win32 service and 2 PHP scripts. 
Once started, the service constantly monitors Skype data folder for new records in all ***main.db*** files for every Skype account. 
If there are new records with chat messages or file transfers - the service sends HTTP request with JSON formatted data to a PHP service, which in turn store the information into MySQL database. 
There is also a simple web search-box for finding conversations.

This application is primarily intended for corporate usage, where the chats with clients may become ***extremely*** important and company can not afford losing them by either intentional or accidental ***deletion*** of local Skype history.

![](https://github.com/tmcdos/skype-log/raw/master/screenshots/1.png)
