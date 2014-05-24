$(document).ready(function() {
createCustWindow();
});


function createCustWindow() {
	$.window({
		title: "Customer Information",
		content: $("#window_block8"), // load window_block8 html content
		containerClass: "my_container",
		headerClass: "my_header",
		frameClass: "my_frame",
		footerClass: "my_footer",
		selectedHeaderClass: "my_selected_header",
		showFooter: true,
		showRoundCorner: true,
		width: 200,                   
		height: 700,                
		//createRandomOffset: {x:200, y:150}
		//createRandomOffset: {x:0, y:0}
	});
}
