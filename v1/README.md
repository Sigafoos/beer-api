# CBW Beer API
	By Dan Conley (dan@communitybeerworks.com)

	Live site: [http://api.communitybeerworks.com/v1](http://api.communitybeerworks.com/v1)

	I've wanted to redo the "on tap" widget on the CBW site for a while now, to make it easier to edit and display beer information. I decided to make it an API, because not only will that easily integrate with backbone.js and the like but hell, maybe you can use it too!

## Endpoints
### Beer
#### /beer
	List of all current beers we make semi-regularly

	Returns: One or more Beer objects

#### /beer/all
	List of all beers we've ever made

	Returns: One or more Beer objects

#### /beer/(id)
	Information of beer with id (id)

	Returns: One Beer object

### On tap
#### /ontap
	Information of what's currently on tap

	Returns: One or more Tap objects

#### /ontap/(id)
	Information of what's on tap (id)

	Returns: One Tap object

### Styles
#### /style
	List of all styles we've made

	Returns: One or more Style objects

#### /style/(id)
	All beers we've made with style (id)

	Returns: One or more Beer objects

### Objects
#### Beer
	* id:		Numeric id of the beer
	* beer:		String representation of the beer name
	* abv:		String representation of the abv content (ie '4.7')
	* style:	String representation of the style (for retrieving numeric ids use /style)
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
	* POST support (will require API key)
	* PUT support (will require API key)
	* Maaaaybe DELETE support? I don't think we/I will need it

### Down the road
	* Integrate with [keg tracker](https://github.com/Sigafoos/Keg-tracker/) to query what our accounts have on tap (might require API key)
	* Possibly merge with the keg site so you can edit keg information as well (would require API key)

	Something you want not on the list? Send me an email and I'll take care of it.
