define(['async!https://maps.google.com/maps/api/js?sensor=false'], function () {

    function GoogleMap() {

        /**
         * Latitude and Longitude co-ordinates
         */
        this.lat = window.latitude;
        this.lng = window.longitude;

        /**
         * Call in the co-ordinates setup for the map
         */
        this.latLngPos(this.lat, this.lng);

        /**
         * Default map options
         */
        this.mapOptions = {
            zoom: 15,
            center: this.latLng,
            mapTypeIds: [google.maps.MapTypeId.ROADMAP],
            disableDefaultUI: false,
            scrollwheel: false
        }
    }

    /**
     * Latitude and Longitude positioning setup for the map
     *
     * @param {String} lat
     * @param {String} lng
     *
     */
    GoogleMap.prototype.latLngPos = function (lat, lng) {
        this.latLng = new google.maps.LatLng(lat, lng);
    }

    /**
     * Creation of the map using the options and id declared
     *
     * @param {String} mapelem
     *
     */
    GoogleMap.prototype.mapCreate = function (mapelem) {
        this.map = new google.maps.Map(mapelem, this.mapOptions);
    }

    /**
     * Custom Marker
     *
     * @param {String} width
     * @param {String} height
     *
     */
    GoogleMap.prototype.mapMarker = function (width, height) {
        // Need to make sure that the image is saved as google-marker.png in order to be found. Also the dimensions need to be correct to work in firefox.
        this.myIcon = new google.maps.MarkerImage(window.site_path + 'public/assets/images/google-marker.png', null, null, null, new google.maps.Size(width, height));
    }

    /**
     * Initiate the google marker to the map
     *
     * @param {String} title
     *
     */
    GoogleMap.prototype.googleMarker = function (title) {

        this.mapMarker(42, 61);

        // If locations not used in the HTML view - use default values
        if (typeof window.locations === 'undefined') {

            this.marker = new google.maps.Marker({
                icon: this.myIcon,
                map: this.map,
                position: this.latLng,
                title: title
            });

        } else {

            this.multiLocations();

            if (window.locations.length > 1) {
                // Bound the map and make it center to all the markers
                this.map.fitBounds(this.bounds);
            } else {
                // Center the map to the 1 location in the HTML view
                this.map.center = this.latLng;
            }
        }
    }

    /**
     * Multiple locations markers
     */
    GoogleMap.prototype.multiLocations = function () {

        // Find all the marker positions
        this.bounds = new google.maps.LatLngBounds();

        for (i = 0; i < window.locations.length; i++) {

            // Find the marker positions and add them to the map
            this.latLngPos(window.locations[i]['lat'], window.locations[i]['long']);

            this.marker = new google.maps.Marker({
                icon: this.myIcon,
                map: this.map,
                position: this.latLng,
                title: window.locations[i]['title']
            });

            // Extend all the positions
            this.bounds.extend(this.marker.position);
        }
    }

    /**
     * Map styles that could be added to a map
     *
     * @param {String} styles
     *
     */
    GoogleMap.prototype.setStyles = function (styles) {

        this.mapOptions.styles = styles;

    }

    /**
     * Initiation of the map with all the elements added
     */
    GoogleMap.prototype.initiate = function () {

        // Google map setup
        this.mapCreate(document.getElementById('js-google-map'));

        // Google marker setup
        this.googleMarker('');

    }

    return GoogleMap;

});