jQuery( document ).ready(function() {

    // Setting up datetimepicker
    for (var i = 1; i < 6; i++) {
        jQuery('.datetimepicker'+i).datetimepicker({
            lang:'de',
            format: 'd.m.Y H:i',
            timepicker:true,
            step: 15,
            defaultDate: false,
            defaultTime: false,
            allowBlank: true

        })
    };



    array = jQuery("#zawiw_poll_id .appointment");

    // Sorting all appointments by participants
    array.sort(SortByCount);

    // Last elem is most participants
    high = array.last().attr("count");

    // Adding colored class for appointments with most participants
    array.each(function(index, elem){
        // Exclude zero
        if (jQuery(elem).attr("count") == high && high != 0) {
            jQuery(elem).addClass("green");
        };
    });
});

// Custom sorting for most participants
function SortByCount(a, b){
  var aName = jQuery(a).attr("count");
  var bName = jQuery(b).attr("count");
  return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
}