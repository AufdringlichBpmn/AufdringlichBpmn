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
			success: function(process){
				var template = $("#template-startProcessDialog").text();
				$("#startProcessDialog [role='main']").html(
					Mustache.render(template, process)
				);
				// search next open user task
				// and set ok-button to opentasks
				$.ajax({
					url: config.server.base_url + config.server.open_usertasks_url,
					data: {},
					success: function(data){
						$.each( data.usertasks, function( index, task ){
							if(task.process_id == process.process._id){
								// change href to new  o u t
								$("#executeUserTask").attr("data-processDefinitionId", task.process_definition_id);
								$("#executeUserTask").attr("data-processId", task.process_id);
								$("#executeUserTask").attr("data-taskId", task.id);
								$("#startProcessDialog [role='main'] .ok").attr("href","#executeUserTask");
								return;
							}
						});
					},
					dataType: "json"
				});
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
				$( "a[data-action='executeUserTask-save']" ).click(function( event ) {
					var atag = $(this);
					var result = atag.attr("data-value");
					var processId = atag.closest("[data-processId]").attr("data-processId");
					var vars = {};
					atag.closest("div").find(":input[data-name]").each(function(i,e){
						vars[atag.attr("data-name")] = atag.val();
					});
					var done = false;
					$.ajax({
						url: config.server.base_url + config.server.evaluate_usertask_url,
						data: {
							process_id: processId,
							task_id: atag.closest("[data-taskId]").attr("data-taskId"),
							vars: JSON.stringify(vars),
							result: result
						},
						success: function(data){
							// search next open user task
							$.ajax({
								url: config.server.base_url + config.server.open_usertasks_url,
								data: {},
								success: function(data){
									var found = false;
									$.each( data.usertasks, function( index, task ){
										if(task.process_id == processId){
											// change href to new  o u t
											$("#executeUserTask").attr("data-taskId", task.id);
											$("#executeUserTask").trigger( "pageshow");
											found = true;
											return;
										}
									});
									if(!found) location.hash="#pageUserTasks";
								},
								error: function(data){
									location.hash="#pageUserTasks";
								},
								dataType: "json"
							});
						},
						dataType: "json"
					});
					return false;
				});
				$( "#executeUserTask-cancel" ).click(function( event ) {
				});
			},
			dataType: "json"
		});
	});
});
