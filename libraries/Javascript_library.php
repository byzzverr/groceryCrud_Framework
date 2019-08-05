<?php

class Javascript_library{
	public function __construct(){
		$this->_CI = &get_instance();
	}

	function data_table_script($dataset, $columns, $order_index, $search=false, $individual_column_search=false, $caption=''){
		if($search){
			$custom_search_button=",{
					text: 'Search',
					action: function () {
						$('#delete-dialog').dialog({});
						$('#delete-dialog').dialog('open');
            		}
            	}";
		}else{
			$custom_search_button='';
		}

		if($individual_column_search){
			$individual_column_search="$(document).ready(function() {
                    $('#report_table tfoot td').each( function () {
                        var title = $(this).text();
                        $(this).html( '<input type=\"text\" placeholder=\"Find '+title+'\" style=\"width:100%;\" />');
                    } );

                    var table = $('#report_table').DataTable();

                    table.columns().every( function () {
                        var that = this;
                 
                        $( 'input', this.footer() ).on( 'keyup change', function () {
                            if ( that.search() !== this.value ) {
                                that
                                    .search( this.value )
                                    .draw();
                            }
                        } );
                    } );
                } );";
		}else{
			$individual_column_search='';
		}

	    $datatable="var dataSet = [ ];
				    ". $dataset."
				    $(document).ready(function() {  
				     $('#report_table').append('<caption ><h4>".$caption."</h4></caption>'); 	
				    var table = $('#report_table').DataTable({
				    	aLengthMenu: [
					        [25, 50, 100, 200, -1],
					        [25, 50, 100, 200, 'All']
					    ],
			            'pagingType': 'full_numbers',
			            'bProcessing': true,
			            'bServerSide': false,
			            'bDeferRender': true,
			            'order': [[ ".$order_index.",'desc' ]],
			            data:dataSet,
			            columns: [".$columns."],
				        dom: 'Bfrtip',
				        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']

				      });


				  });
				  $individual_column_search";
		    
		    
	    return $datatable;
	}


    function data_table_script_2($dataset, $columns, $order_index, $search=false, $individual_column_search=false, $caption=''){
		if($search){
			$custom_search_button=",{
					text: 'Search',
					action: function () {
						$('#delete-dialog').dialog({});
						$('#delete-dialog').dialog('open');
            		}
            	}";
		}else{
			$custom_search_button='';
		}

		if($individual_column_search){
			$individual_column_search="$(document).ready(function() {
                    $('#report_table tfoot td').each( function () {
                        var title = $(this).text();
                        $(this).html( '<input type=\"text\" placeholder=\"Find '+title+'\" style=\"width:100%;\" />');
                    } );

                    var table = $('#report_table2').DataTable();

                    table.columns().every( function () {
                        var that = this;
                 
                        $( 'input', this.footer() ).on( 'keyup change', function () {
                            if ( that.search() !== this.value ) {
                                that
                                    .search( this.value )
                                    .draw();
                            }
                        } );
                    } );
                } );";
		}else{
			$individual_column_search='';
		}

	    $datatable="var dataSet2 = [ ];
				    ". $dataset."
				    $(document).ready(function() {  
				     $('#report_table2').append('<caption ><h4>".$caption."</h4></caption>'); 	
				    var table = $('#report_table2').DataTable({
			            'pagingType': 'full_numbers',
			            'bProcessing': true,
			            'bServerSide': false,
			            'bDeferRender': true,
			            'order': [[ ".$order_index.",'desc' ]],
			            data:dataSet2,
			            columns: [".$columns."],
				        dom: 'Bfrtip',
				        buttons: ['copy']

				      });


				  });
				  $individual_column_search";
		    
		    
	    return $datatable;
	}

	public function bar_chart_script($labels, $values){
	    $chart="var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
	            var barChartData = {
	            labels : [".$labels."],
	            datasets : [{fillColor : '#c8ba9e',
	                strokeColor : '#c8ba9e',
	                highlightFill : '#bca789',
	                highlightStroke : '#b4a085',
	                data : [".$values."]}]
	            }
	            window.onload = function(){
	                var ctx = document.getElementById('canvas2').getContext('2d');
	                window.myBar = new Chart(ctx).Bar(barChartData,{responsive : true});
	            }";

	    return $chart;

	}

	public function colored_bar_chart_script($labels, $values, $barColors){
	    $chart='var barChartData = {
            	labels: ['.$labels.'],
	            datasets: [
	                {
	                    label: "Fridge Temperature Chart",
	                    fillColor: "rgba(220,220,220,0.5)", 
	                    strokeColor: "rgba(220,220,220,0.8)", 
	                    highlightFill: "rgba(220,220,220,0.75)",
	                    highlightStroke: "rgba(220,220,220,1)",
	                    data: ['.$values.']
	                }
	            ]
	        };
	        var options = {
	            scaleBeginAtZero: false,
	            responsive: true,
	            scaleStartValue : -50 
	        };
	        window.onload = function(){
	            var ctx = document.getElementById("canvas").getContext("2d");
	            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
	                  responsive : true
	            });

	            //nuevos colores
	           '.$barColors.'
	            myObjBar.update();
	        }';

	    return $chart;

	}




	
}