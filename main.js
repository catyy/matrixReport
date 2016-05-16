 TSPOS.EventManager.addEventListener( 'receipt_product_view', function( view ) {

 var button = jQuery('<button  type="button" class="btn-action-primary "><span class="text-24px icon_table"></span></button>');

 $(button).insertBefore('.btn-scale-product');

 button.click(function(){

     var sessionKey  = ErplyAPI.sessionKey;
     var clientCode  = ErplyAPI.clientCode;
     var productData = TSPOS.Model.Document.currentProduct;
     var name = productData.name;
     var productId = productData.productID;

    var data = {
        sessionKey: sessionKey,
        clientCode: clientCode,
        name: name,
        productId: productId
    };

     $.post("https://matrixproductstockreport.azurewebsites.net/index.php", data, function(result){
        // window.open('https://matrixproductstockreport.azurewebsites.net/index.php', "Report", "height=300,width=300");
        showReport(result);
  
     });

 });
 });
 showReport = function(result) {
   var params = {
   viewType: 'show-report',
   dismiss: true,
   modal: true
 };

   var view = TSPOS.UI.openView(params);

   $('#report').html(result);
  
   view.find('.button-yes').on('click', function(e) {
   });
 };


 function getNewTableData(){
 
 var value = document.getElementById("view").value; 
 var sessionKey  = ErplyAPI.sessionKey;
     var clientCode  = ErplyAPI.clientCode;
     var productData = TSPOS.Model.Document.currentProduct;
     var name = productData.name;
     var productId = productData.productID;

    var data = {
        sessionKey: sessionKey,
        clientCode: clientCode,
        name: name,
        productId: productId,
        tableType: value
    };

     $.post("https://matrixproductstockreport.azurewebsites.net/index.php", data, function(result){
        // window.open('https://matrixproductstockreport.azurewebsites.net/index.php', "Report", "height=300,width=300");
         $('#report').html(result);
        
  
     });

 };
 

 Template["show-report"] = '<div  class="modal"><div class="modal-dialog"><div class="modal-content ">' +
 '<div class="modal-header">' +
 '<span trans class="text-24px bold">Report</span>' +
 '<div class="modal-actions pull-right" style="padding-right: 12px;">' +
 '<button type="button" class="close">&times;</button>' +
 '</div>' +
 '<div class="modal-body scrollable" style="padding-top: 16px; max-height: 626px; overflow-x: scroll;merge:auto;">' +
 '<div id="report"></div>' +
 '</div>' +
 '<div class="modal-footer" style="margin-top:0px;">' +
 '<button type="button" style="float:right;border-radius: 4px;color: #232323;background-color: #a5c536;font-family: Proxima Nova Bold;font-weight: 300;text-transform: uppercase;position: relative;width: 160px;height: 70px;font-size: 24px!important;" class="close">Close</button>' +
 '</div>' +
 '</div></div></div></div>';
