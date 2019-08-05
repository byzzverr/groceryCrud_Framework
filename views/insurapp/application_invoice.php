<style>
    .invoice-box{
        font-family:'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;

    }
    
    .invoice-box table{
        width:100%;
        line-height:inherit;
        text-align:left;
    }
    
    .invoice-box table td{
        padding:5px;
        vertical-align:top;
    }
    
    .invoice-box table tr td:nth-child(2){
        text-align:right;
    }
    
    .invoice-box table tr.top table td{
        padding-bottom:20px;
    }
    
    .invoice-box table tr.top table td.title{
        color:#333;
    }
    
    .invoice-box table tr.information table td{
        padding-bottom:40px;
    }
    
    .invoice-box table tr.heading td{
        background:#eee;
        border-bottom:1px solid #ddd;
        font-weight:bold;
    }
    
    .invoice-box table tr.details td{
        padding-bottom:20px;
    }
    
    .invoice-box table tr.item td{
        border-bottom:1px solid #eee;
    }
    
    .invoice-box table tr.item.last td{
        border-bottom:none;
    }
    
    .invoice-box table tr.total td:nth-child(2){
        border-top:2px solid #eee;
        font-weight:bold;
    }
    
    @media only screen and (max-width: 600px) {
        .invoice-box table tr.top table td{
            width:100%;
            display:block;
            text-align:center;
        }
        
        .invoice-box table tr.information table td{
            width:100%;
            display:block;
            text-align:center;
        }
    }
</style>

<style type="text/css">
	/* Client-specific Styles */
	#outlook a{padding:0;} /* Force Outlook to provide a "view in browser" button. */
	body{width:100% !important;} .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail to display emails at full width */
	body{-webkit-text-size-adjust:none;} /* Prevent Webkit platforms from changing default text sizes. */
	
	/* Reset Styles */
	body{margin:0; padding:0;}
	img{border:0; height:auto; line-height:100%; outline:none; text-decoration:none;}
	table td{border-collapse:collapse;}
	#backgroundTable{height:100% !important; margin:0; padding:0; width:100% !important;}
	
	/* Template Styles */
	
	/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: COMMON PAGE ELEMENTS /\/\/\/\/\/\/\/\/\/\ */

	/**
	* @tab Page
	* @section background color
	* @tip Set the background color for your email. You may want to choose one that matches your company's branding.
	* @theme page
	*/
	body, #backgroundTable{
		/*@editable*/ background-color:#8ec63f;
	}
	
	/**
	* @tab Page
	* @section email border
	* @tip Set the border for your email.
	*/
	#templateContainer{
		/*@editable*/ border:0;
	}
	
	/**
	* @tab Page
	* @section heading 1
	* @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
	* @style heading 1
	*/
	h1, .h1{
		/*@editable*/ color:#202020;
		display:block;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:40px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		margin-top:2%;
		margin-right:0;
		margin-bottom:1%;
		margin-left:0;
		/*@editable*/ text-align:left;
	}

	/**
	* @tab Page
	* @section heading 2
	* @tip Set the styling for all second-level headings in your emails.
	* @style heading 2
	*/
	h2, .h2{
		/*@editable*/ color:#404040;
		display:block;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:18px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		margin-top:2%;
		margin-right:0;
		margin-bottom:1%;
		margin-left:0;
		/*@editable*/ text-align:left;
	}

	/**
	* @tab Page
	* @section heading 3
	* @tip Set the styling for all third-level headings in your emails.
	* @style heading 3
	*/
	h3, .h3{
		/*@editable*/ color:#606060;
		display:block;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:16px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		margin-top:2%;
		margin-right:0;
		margin-bottom:1%;
		margin-left:0;
		/*@editable*/ text-align:left;
	}

	/**
	* @tab Page
	* @section heading 4
	* @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
	* @style heading 4
	*/
	h4, .h4{
		/*@editable*/ color:#808080;
		display:block;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:14px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		margin-top:2%;
		margin-right:0;
		margin-bottom:1%;
		margin-left:0;
		/*@editable*/ text-align:left;
	}
	
	/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: PREHEADER /\/\/\/\/\/\/\/\/\/\ */
	
	/**
	* @tab Header
	* @section preheader style
	* @tip Set the background color for your email's preheader area.
	* @theme page
	*/
	#templatePreheader{
		/*@editable*/ background-color:#FAFAFA;
	}
	
	/**
	* @tab Header
	* @section preheader text
	* @tip Set the styling for your email's preheader text. Choose a size and color that is easy to read.
	*/
	.preheaderContent div{
		/*@editable*/ color:#707070;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:10px;
		/*@editable*/ line-height:100%;
		/*@editable*/ text-align:left;
	}
	
	/**
	* @tab Header
	* @section preheader link
	* @tip Set the styling for your email's preheader links. Choose a color that helps them stand out from your text.
	*/
	.preheaderContent div a:link, .preheaderContent div a:visited, /* Yahoo! Mail Override */ .preheaderContent div a .yshortcuts /* Yahoo! Mail Override */{
		/*@editable*/ color:#336699;
		/*@editable*/ font-weight:normal;
		/*@editable*/ text-decoration:underline;
	}
	
	/**
	* @tab Header
	* @section social bar style
	* @tip Set the background color and border for your email's footer social bar.
	*/
	#social div{
		/*@editable*/ text-align:right;
	}

	/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: HEADER /\/\/\/\/\/\/\/\/\/\ */

	/**
	* @tab Header
	* @section header style
	* @tip Set the background color and border for your email's header area.
	* @theme header
	*/
	#templateHeader{
		/*@editable*/ background-color:#FFFFFF;
		/*@editable*/ border-bottom:5px solid #505050;
	}
	
	/**
	* @tab Header
	* @section left header text
	* @tip Set the styling for your email's header text. Choose a size and color that is easy to read.
	*/
	.leftHeaderContent div{
		/*@editable*/ color:#202020;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:32px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		/*@editable*/ text-align:right;
		/*@editable*/ vertical-align:middle;
	}
	
	/**
	* @tab Header
	* @section right header text
	* @tip Set the styling for your email's header text. Choose a size and color that is easy to read.
	*/
	.rightHeaderContent div{
		/*@editable*/ color:#202020;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:32px;
		/*@editable*/ font-weight:bold;
		/*@editable*/ line-height:100%;
		/*@editable*/ text-align:left;
		/*@editable*/ vertical-align:middle;
	}

	/**
	* @tab Header
	* @section header link
	* @tip Set the styling for your email's header links. Choose a color that helps them stand out from your text.
	*/
	.leftHeaderContent div a:link, .leftHeaderContent div a:visited, .rightHeaderContent div a:link, .rightHeaderContent div a:visited{
		/*@editable*/ color:#336699;
		/*@editable*/ font-weight:normal;
		/*@editable*/ text-decoration:underline;
	}

	#headerImage{
		height:auto;
		max-width:180px !important;
	}
	
	/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: MAIN BODY /\/\/\/\/\/\/\/\/\/\ */
	
	/**
	* @tab Body
	* @section body style
	* @tip Set the background color for your email's body area.
	*/
	#templateContainer, .bodyContent{
		/*@editable*/ background-color:#FDFDFD;
	}
	
	/**
	* @tab Body
	* @section body text
	* @tip Set the styling for your email's main content text. Choose a size and color that is easy to read.
	* @theme main
	*/
	.bodyContent div{
		/*@editable*/ color:#505050;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:14px;
		/*@editable*/ line-height:150%;
		/*@editable*/ text-align:left;
	}
	
	/**
	* @tab Body
	* @section body link
	* @tip Set the styling for your email's main content links. Choose a color that helps them stand out from your text.
	*/
	.bodyContent div a:link, .bodyContent div a:visited, /* Yahoo! Mail Override */ .bodyContent div a .yshortcuts /* Yahoo! Mail Override */{
		/*@editable*/ color:#336699;
		/*@editable*/ font-weight:normal;
		/*@editable*/ text-decoration:underline;
	}
	
	.bodyContent img{
		display:inline;
		height:auto;
	}
	
	/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: FOOTER /\/\/\/\/\/\/\/\/\/\ */
	
	/**
	* @tab Footer
	* @section footer style
	* @tip Set the background color and top border for your email's footer area.
	* @theme footer
	*/
	#templateFooter{
		/*@editable*/ background-color:#FAFAFA;
		/*@editable*/ border-top:3px solid #909090;
	}
	
	/**
	* @tab Footer
	* @section footer text
	* @tip Set the styling for your email's footer text. Choose a size and color that is easy to read.
	* @theme footer
	*/
	.footerContent div{
		/*@editable*/ color:#707070;
		/*@editable*/ font-family:Arial;
		/*@editable*/ font-size:11px;
		/*@editable*/ line-height:125%;
		/*@editable*/ text-align:left;
	}
	
	/**
	* @tab Footer
	* @section footer link
	* @tip Set the styling for your email's footer links. Choose a color that helps them stand out from your text.
	*/
	.footerContent div a:link, .footerContent div a:visited, /* Yahoo! Mail Override */ .footerContent div a .yshortcuts /* Yahoo! Mail Override */{
		/*@editable*/ color:#336699;
		/*@editable*/ font-weight:normal;
		/*@editable*/ text-decoration:underline;
	}
	
	.footerContent img{
		display:inline;
	}
	
	/**
	* @tab Footer
	* @section social bar style
	* @tip Set the background color and border for your email's footer social bar.
	* @theme footer
	*/
	#social{
		/*@editable*/ background-color:#FFFFFF;
		/*@editable*/ border:0;
	}
	
	/**
	* @tab Footer
	* @section social bar style
	* @tip Set the background color and border for your email's footer social bar.
	*/
	#social div{
		/*@editable*/ text-align:left;
	}
	
	/**
	* @tab Footer
	* @section utility bar style
	* @tip Set the background color and border for your email's footer utility bar.
	* @theme footer
	*/
	#utility{
		/*@editable*/ background-color:#FAFAFA;
		/*@editable*/ border-top:0;
	}

	/**
	* @tab Footer
	* @section utility bar style
	* @tip Set the background color and border for your email's footer utility bar.
	*/
	#utility div{
		/*@editable*/ text-align:left;
	}
	
	#monkeyRewards img{
		max-width:170px !important;
	}
</style>
<style tyle="text/css">
   @media print
   {
      .well {display: none;}
      .brand {display: none;}
      .buttons {display: none;}
      .header {display: none;}
      .nav {display: none;}
     
   }

   @page {
  size: auto;
  margin: 0;
       }

 
</style>


</head>
<div class="container">
<div class="well">
		<h1><?php echo $page_title?>&nbsp;&nbsp;</h1>
</div>

<div class="buttons">

	<button onclick="history.go(-1)" class="btn btn-primary">&nbsp;&nbsp; Back &nbsp;&nbsp;</button>
	<a href="" onclick="window.print();" class="btn btn-primary">Print</a>
	<a href="/insurapp/financial/resend_invoice/<?=$master_company_id?>/<?=$invoice_id?>/<?=$branch_id?>"  class="btn btn-primary">Resend Payment</a>
</div><br/>

	<table border="0" cellpadding="10" cellspacing="0" style="width: 98%">
		<tr>
	    	<td valign="top" style="border:none">
	            <strong> Payment Request #: <?=$invoice_id?></strong>
	        </td>
	    </tr>
	</table>

	<div class=" " style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; border: solid thin; border-radius: 5px">
	<br/>

		<center>
	            <!--  -->
	  			<table style="width: 98%; line-height: inherit;text-align: left; border-radius: 5px;">
	                <tr>
	                    <td style="padding: 5px;vertical-align: top;padding-bottom: 40px; border:none;"><p>
	                    	<strong>Insurapp PTY LTD</strong><br>
							Building 3B, First Floor, Glen Eagles park,<br>
							10 Flanders drive, Mount Edgecombe, 4300<br>
							help@insurapp.co.za<br>
							0849846644
						</p>
	                    </td>
	                    
	                    <td style="padding: 5px;vertical-align: top;text-align: right;padding-bottom: 40px; border:none;">
	                        <strong><?=$master_company_info['name']?></strong><br>
	                        Bank Name : <?=$master_company_info['bank']?><br>
	                        Bank Account : <?=$master_company_info['bank_accno']?><br>
	                        Bank Code : <?=$master_company_info['bank_bcode']?><br>
	                        Branch : <?=$master_company_info['bank_branch']?>
	                    </td>
	                </tr>
	            </table>
	            </center>
	        <br/>

	</div>
      <br/>
	<div class=" " style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; border: solid thin; border-radius: 5px">
	<center>
	<br/>
		<table style="width: 98%;line-height: inherit;text-align: left; background:#FFF; ">

              	<tr>
					
					<th style="padding: 5px;vertical-align: top;text-align: left;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold; ">Product </th>
					<th style="padding: 5px;vertical-align: top;text-align: left;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold; ">Product Count</th>
					<th style="padding: 5px;vertical-align: top;text-align: left;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold; ">Total Premium</th>
					
				</tr>

			<? 
			$total=0;
			foreach ($invoice_info as $key => $value) { ?>

				<tr>
					
					<td style="padding: 5px;vertical-align: top;border-bottom: 1px solid #eee;"><?=$value['product_name']?></td>
					<td style="padding: 5px;vertical-align: top;border-bottom: 1px solid #eee;"><?=$value['product_count']?></td>
					<td style="padding: 5px;vertical-align: top;border-bottom: 1px solid #eee;"><?=$value['total_premium']?></td>
					
					
				</tr>
				<? $total+=$value['total_premium'];?>
			<? }?>
				<tr class="total">
		            <td style="padding: 5px;vertical-align: top;text-align: right;background: #eee;border-bottom: 1px solid #ddd;font-weight: bold; " colspan="6">
		               Total: <?=number_format($total,2,'.',', ')?>
		            </td>
		       	</tr>
              </table>

              <br/>

         </center>

      </div><br/>

		<div class=" " style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; border: solid thin; border-radius: 5px"><br/>
			<center>
            <table border="0" cellpadding="10" cellspacing="0" style="width:98%">
               
                <tr>
                    <td valign="top" width="440" >
                        <div mc:edit="std_footer">
							<em>Copyright &copy; <?=date("Y")?> INSURAPP, All rights reserved.</em>
							<br />
							<strong>Our mailing address is:</strong>
							<br />
							help@insurapp.co.za
                        </div>
                    </td>
                   
                    <td  width="190" id="monkeyRewards" style="text-align: center;">
							<p>We are here to help</p>
							 <img src="/assets/insurapp/img/insurapp.png" width="40px" alt-"Logo"/>
                    </td>
                </tr>  
            </table>
            </center><br/>
      	</div>
      </div>
    </div>
  </div>


  