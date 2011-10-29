Albanel
====

What?
-----
Albanel is a tiny PHP proxy designed to capture all traffic in order to
analyse it later.


Why?
----
Because [Scarab](https://www.owasp.org/index.php/Category:OWASP_WebScarab_Project) was not working properly with HTTPS traffic. Albanel also
allows the input to be edited on-the-fly if you want to, just edit
HttpRequest::getInput(). Better (or actual) support for this may be
added in the future.


Why the name?
-------------
I needed a name for a quick and dirty tool that monitor all HTTP traffic,
[Christine Albanel](https://en.wikipedia.org/wiki/Christine_Albanel)
immediately came to my mind.


How?
---
Albanel is written in PHP 5.3. To capture traffic, redirect an host to an
IP where Albanel is hosted, it will take care of saving the contents and
routing the request to the actual host.

Here is a lighttpd configuration snippet that may prove useful :

    $HTTP["host"] =~ "fake" {
       server.document-root = "/var/www/albanel"
       url.rewrite = (
          "^(.*)$" => "index.php"
       )
    }


Who? When?
---------
    $ whoami
    leo
    $ date
	Sat Oct 29 19:07:24 CEST 2011

