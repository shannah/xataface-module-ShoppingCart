var provincesSelect;
function updateProvinces(countrySelect){
	if ( !countrySelect ){
		countrySelect = document.getElementById('country-select');
	}
	var country = countrySelect.options[countrySelect.selectedIndex].value;
	provincesSelect = document.getElementById('province-select');
	getDataReturnText(DATAFACE_SITE_HREF+'?-action=get_shipping_provinces&country='+escape(country),
		function(text){
			provincesSelect.options.length = 0;
			eval('var provinces = '+text+';');
			if ( provinces.length > 0){
				provincesSelect.options[0] = new Option('Please select...', '');
			} else {
				provincesSelect.options[0] = new Option('N/A', -1);
			}
			for (var i=0; i<provinces.length; i++){
				provincesSelect.options[provincesSelect.options.length] = new Option(provinces[i]['name'],provinces[i]['code']);
				
			}
		});
	
}

registerOnloadHandler(updateProvinces);