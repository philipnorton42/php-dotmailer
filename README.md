PHP-Dotmailer
=============

A class to interact with the dotMailer API. In order to use this class you will need an account (with API access) from dotMailer (http://www.dotmailer.com).

Usage:

To get up and running just provide the class with your API username and password;

	$dotmailer = new DotMailer('username', 'password');

You can then interact with the methods using the same method names as described in the API documentation (http://www.dotmailer.co.uk/api/). For example to list all address books on your account just use the following

	$addressBooks = $dotmailer->ListAddressBooks();
	foreach ($addressBooks as $book) {
		print $book->ID  . ' => ' . $book->Name . PHP_EOL;
	}

Not all API methods are implemented just yet, but more will follow.

This project was sponsored by Access (http://www.accessadvertising.co.uk)
