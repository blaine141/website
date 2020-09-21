var projectOpen = false;
var navigating = false;
/* Set the width of the side navigation to 250px and the left margin of the page content to 250px */
function openProject(project) {
    $("#projectFrame").attr("src", "projects/"+project+".html")
    document.getElementById("mySideproject").style.width = ($("#viewport").width()) + "px";
    document.getElementById("viewport").style.marginRight = ($("#viewport").width() * .95) + "px";
    $("li").toggleClass("nav-collapsed");
    projectOpen = true;
}

/* Set the width of the side navigation to 0 and the left margin of the page content to 0 */
function closeProject() {
    document.getElementById("mySideproject").style.width = "0";
    document.getElementById("main").style.marginRight = "0";
    $("li").toggleClass("nav-collapsed");
    projectOpen = false;
}
$("#viewport").scroll(function () {
    $(".title-div").css("opacity", 1 - $("#viewport").scrollTop() / $(document).height() * 4);
});
$(document).on('click', 'a', function (event) {
    if ($(this).attr('href')) {
        if ($(this).attr('href') == "#about") {
            $(".navbar .nav > li").removeClass('active');
            $(".first").addClass('active');
        }
        if ($(this).attr('href')[0] == '#') {
            event.preventDefault();
            navigating = true;
            console.log($($.attr(this, 'href')).offset().top)
            $('html, #viewport').animate({
                scrollTop: $($.attr(this, 'href')).offset().top + $("#viewport").scrollTop()
            }, 500, function() {navigating = false});
        }
    }
});
var lnStickyNavigation = $("#viewport").height() - 60;
updateHeight();
$(window).on('resize', function () {
    updateHeight();
    if (projectOpen) {
        $("#mySideproject").toggleClass("transitions");
        $("#main").toggleClass("transitions");
        document.getElementById("mySideproject").style.width = ($(window).width()) + "px";
        document.getElementById("main").style.marginRight = ($(window).width() * .95) + "px";
        $("#mySideproject").toggleClass("transitions");
        $("#main").toggleClass("transitions");
    }
});
function updateHeight() {
    lnStickyNavigation = $(window).height() - 60;

    $('.navbar').css({ top: ($(window).height()) + 'px' });
    $('.jumbotron').css({ height: ($(window).height()) + 'px' });
}
$("#viewport").on('scroll', function () {
    stickyNavigation();
    if(!navigating) {
        $(".navbar .nav a").each(function (index, element) {
            if(element.hash[0] == '#'){
                if($(window).height()/2 >= $(element.hash).offset().top) {
                    $(".navbar .nav > li").removeClass('active');
                    $(element.parentElement).addClass('active');
                }
            }
        })
    }
});
$(".navbar .nav > li").on('click', function () {
    if (!$(this).hasClass("nav-back")) {
        $(".navbar .nav > li").removeClass('active');
        $(this).addClass('active');
    }
});
stickyNavigation();
function stickyNavigation() {
    if ($("#viewport").scrollTop() > lnStickyNavigation) {
        $('body').addClass('fixed');
    } else {
        $('body').removeClass('fixed');
    }
}