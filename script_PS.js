let menu = document.querySelector("#menu-icon");
let navbar = document.querySelector(".navbar");

menu.onclick = () => {
	menu.classList.toggle("bx-x");
	navbar.classList.toggle("open");
}

// $(screen).scroll(function(){
// 	if($(screen).scrollTop()){
// 		$("header").addClass("black");
// 	}
// 	else{
// 		$("header").removeClass("black");
// 	}
// });

const headerEl = document.querySelector('.header');

window.addEventListener('scroll', () => {
	if(window.scrollY > 200){
		headerEl.classList.add('header-scrolled');
	}
	else if(window.scrollY < 200){
		headerEl.classList.remove('header-scrolled');
	}
}
);

// Tabbed Menu
function openMenu(evt, menuName) {
  var i, x, tablinks;
  x = document.getElementsByClassName("menu");
  for (i = 0; i < x.length; i++) {
     x[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablink");
  for (i = 0; i < x.length; i++) {
     tablinks[i].className = tablinks[i].className.replace(" w3-red", "");
  }
  document.getElementById(menuName).style.display = "block";
  evt.currentTarget.firstElementChild.className += " w3-red";
}
document.getElementById("myLink").click();
// end of Tabbed Menu

// Slider
  var sickPrimary = {
        autoplay: true,
        autoplaySpeed: 2400,
        slidesToShow: 2,
        slidesToScroll: 1,
        speed: 1800,
        cssEase: 'cubic-bezier(.84, 0, .08, .99)',
        asNavFor: '.text-slider',
        centerMode: true,
        prevArrow: $('.prev'),
        nextArrow: $('.next')
  }

  var sickSecondary = {
        autoplay: true,
        autoplaySpeed: 2400,
        slidesToShow: 1,
        slidesToScroll: 1,
        speed: 1800,
        cssEase: 'cubic-bezier(.84, 0, .08, .99)',
        asNavFor: '.image-slider',
        prevArrow: $('.prev'),
        nextArrow: $('.next')
  }

  $('.image-slider').slick(sickPrimary);
  $('.text-slider').slick(sickSecondary);
//end of Slider

// popup ng submit form
function togglePopup(){
  document.getElementById("submitted").classList.toggle("active");
}