$("li").on("click", function(){
    $("li").find(".active").removeClass("active");
    $(this).addClass("active");
 });