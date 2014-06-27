var config = {
	server : {
		base_url: "testStub/",
//		process_definitions_url:"../connector/processdefinitions.php",
		process_definitions_url:"processdefinitions.js",
		open_usertasks_url:"open_usertasks.js",
		usertask_url:"usertask.js"
	}
};

$(document).ready(function() {
	$( "#pageProcessDefinitions" ).on( "pageshow", function( event ) {
		$.ajax({
			url: config.server.base_url + config.server.process_definitions_url,
			data: {},
			success: function(data){
				var template = $("#template-pageProcessDefinitions").text();
				$("#pageProcessDefinitions [role='main']").html(
					Mustache.render(template, data)
				);
			},
			dataType: "json"
		});
	});
	$( "#pageUserTasks" ).on( "pageshow", function( event ) {
		$.ajax({
			url: config.server.base_url + config.server.open_usertasks_url,
			data: {},
			success: function(data){
				var template = $("#template-pageUserTasks").text();
				$("#pageUserTasks [role='main']").html(
					Mustache.render(template, data)
				);
			},
			dataType: "json"
		});
	});
	$( "#executeUserTask" ).on( "pageshow", function( event ) {
		$.ajax({
			url: config.server.base_url + config.server.usertask_url,
			data: {},
			success: function(data){
				var template = $("#template-executeUserTask").text();
				$("#executeUserTask [role='main']").html(
					Mustache.render(template, data)
				);
				$( "#executeUserTask-save" ).click(function( event ) {
					alert("save");
				});
				$( "#executeUserTask-cancel" ).click(function( event ) {
					alert("zurück");
				});
			},
			dataType: "json"
		});
	});
});
