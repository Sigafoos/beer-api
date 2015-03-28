# CBW Beer API
By Dan Conley (dan@communitybeerworks.com)

Live site: [http://api.communitybeerworks.com/v1](http://api.communitybeerworks.com/v1)

I've wanted to redo the "on tap" widget on the CBW site for a while now, to make it easier to edit and display beer information. I decided to make it an API, because not only will that easily integrate with backbone.js and the like but hell, maybe you can use it too!

## Endpoints
### Beer
#### /beers
	List of all current beers we make semi-regularly

	Returns: One or more Beer objects

#### /beers/all
	List of all beers we've ever made

	Returns: One or more Beer objects

#### /beers/(id)
	Information of beer with id (id)

	Returns: One Beer object

### On tap
#### /taps
	Information of what's currently on tap

	Returns: One or more Tap objects

#### /taps/(id)
	Information of what's on tap (id)

	Returns: One Tap object

### Styles
#### /styles
	List of all styles we've made

	Returns: One or more Style objects

#### /styles/(id)
	All beers we've made with style (id)

	Returns: One or more Beer objects

### Objects
#### Beer
* id:		Numeric id of the beer
* beer:		String representation of the beer name
* abv:		String representation of the abv content (ie '4.7')
* style:	A Style object
* description:	Optional string description of the beer

#### Tap
* id:		Numeric id of the tap
* tap:		String representation of the tap (ie "1" or "Cask")
* beer:		A Beer object

#### Style
* id:		Numeric id of the style
* style:	String representation of the style

## To do
* Make a fancy acronym for it (high priority)
* Maaaaybe DELETE support? I don't think we/I will need it

### Down the road
* Integrate with [keg tracker](https://github.com/Sigafoos/Keg-tracker/) to query what our accounts have on tap (might require API key)
* Possibly merge with the keg site so you can edit keg information as well (would require API key)

Something you want not on the list? Send me an email and I'll take care of it.

## Installation
You only need to install if you'd like to run this with your own data: to grab CBW's, use http://api.communitybeerworks.com/v1

* Move config-sample.inc.php to config.inc.php
* Change your database information
* Run install.php

Note: This shares the beer table with the keg tracker, so if you use both they'll auto update with new and archived beers. If you have an existing keg database you'll need to manually add these columns:

* style int
* abv varchar(10)
* description varchar(500)

`style` and `abv` should be `NOT NULL` but you'll need to populate those fields first to avoid an error.
