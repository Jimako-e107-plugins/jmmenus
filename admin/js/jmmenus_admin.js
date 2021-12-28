$(document).ready(function() {

	// Ajax ////////////////////////////////
  // used working solution by Spinnet Plannet. No idea how to work with inbuild ajax support
  // this script is loaded only for some url, otherwise there could be conflict with JM Canonical plugin

	function ADMINajax(data, callback){		
		$.post("ajax.php", data, function(response) {
      localStorage.setItem("swal",
        $.notify(response, { 
        type: 'success' 
        }) 
      );
      location.reload();
      localStorage.getItem("swal");
    	});
	}; 

	// Delete not used menus  //////////////////////// 
	$(document).on('click', '#delete_notusedmenus', function() {
 
    /* 
    var field = $(this);
    var table = $(this).attr('table');     
    var idName = $(this).attr('idName');  */
		ADMINajax({func : 'DELETE_notusedmenus' });       
   /* window.location.reload() ;    */
	});  
});
