var config = {
	server : {
		base_url: "testStub/",
		process_definitions_url:"../connector/processdefinitions.php",
		start_process_url:"../connector/startProcess.php",
		open_usertasks_url:"../connector/openUserTasks.php",
		usertask_url:"../connector/usertask.php",
		evaluate_usertask_url:"../connector/evaluateUserTask.php"
//		process_definitions_url:"processdefinitions.js",
//		open_usertasks_url:"open_usertasks.js",
//		usertask_url:"usertask.js"
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
				$("a[href='#startProcessDialog']").click(function( event ) {
					$("#startProcessDialog").attr("data-processDefinitionId", $(this).closest("[data-processDefinitionId]").attr("data-processDefinitionId"));
				});
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
				$("a[data-action]").click(function( event ) {
					$("#executeUserTask").attr("data-processId", $(this).closest("[data-processId]").attr("data-processId"));
					$("#executeUserTask").attr("data-taskId", $(this).closest("[data-taskId]").attr("data-taskId"));
				});
			},
			dataType: "json"
		});
	});
	$( "#startProcessDialog" ).on( "pageshow", function( event ) {
		$.ajax({
			url: config.server.base_url + config.server.start_process_url,
			data: {
				process_definition_id: $(this).closest("[data-processDefinitionId]").attr("data-processDefinitionId")
			},
			success: function(data){
				var template = $("#template-startProcessDialog").text();
				$("#startProcessDialog [role='main']").html(
					Mustache.render(template, data)
				);
			},
			dataType: "json"
		});
	});
	$( "#executeUserTask" ).on( "pageshow", function( event ) {
		$.ajax({
			url: config.server.base_url + config.server.usertask_url,
			data: {
				process_id: $(this).closest("[data-processId]").attr("data-processId"),
				task_id: $(this).closest("[data-taskId]").attr("data-taskId")
			},
			success: function(data){
				var template = $("#template-executeUserTask").text();
				$("#executeUserTask [role='main']").html(
					Mustache.render(template, data)
				);
				$( "#executeUserTask-save" ).click(function( event ) {
					var result;
					if($(this).closest("div").find("input:checked.default").size()>0)
						result = $(this).closest("div").find("input[type='text'].default").val();
					else
						result = $(this).closest("div").find("input:checked").val();
					$.ajax({
						url: config.server.base_url + config.server.evaluate_usertask_url,
						data: {
							process_id: $(this).closest("[data-processId]").attr("data-processId"),
							task_id: $(this).closest("[data-taskId]").attr("data-taskId"),
							result: result
						},
						success: function(data){
						},
						dataType: "json"
					});
				});
				$( "#executeUserTask-cancel" ).click(function( event ) {
				});
			},
			dataType: "json"
		});
	});
});
