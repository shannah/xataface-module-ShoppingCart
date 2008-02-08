Shopping Cart v 0.1
(c) 2007-2008 Web Lite Solutions Corp, All rights reserved

This program is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your
option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


About this module:
------------------

This is a module for the Xataface Application Framework that adds a shopping
cart to the application.  Any record that is recognizable by the InventoryItem
Ontology (included with this module) may be added to the cart.  Essentially any
record can be wrapped to make it into a "product" that can be added to the cart.

Development Status:
-------------------

	Under development

Requirements:
-------------

	PHP 5 or higher
	
	Xataface 1.0 or higher

Installation:
-------------

	1. Download the ShoppingCart directory into your Xataface modules directory.
	
	2. Add the following to your application's conf.ini file in the [_modules]
	   section.
	   
	   	modules_ShoppingCart="modules/ShoppingCart/ShoppingCart.php"
	   	
