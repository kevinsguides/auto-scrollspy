
//add event listener for dom ready
document.addEventListener("DOMContentLoaded", function(event) {



    //look for ul.autoss-nav
    var nav = document.querySelector('.autoss-nav');
    //this var is equal to nav data-listelem
    var listElem = nav.getAttribute('data-listelem');

    //make first item active by default
    nav.querySelector(listElem + ' a').classList.add('active');

    function removeActive() {
        var active = nav.querySelector(listElem + ' a.active');
        if (active) {
            active.classList.remove('active');
        }
    }

    //get all the hrefs from the links under nav
    var links = nav.querySelectorAll(listElem + ' a');
    var hrefs = [];
    for (var i = 0; i < links.length; i++) {
        hrefs.push(links[i].getAttribute('href'));
    }

    //log the y position of each element by id
    var positions = [];
    for (var i = 0; i < hrefs.length; i++) {
        var id = hrefs[i].replace('#', '');
        var el = document.getElementById(id);
        if (el) { // Check if the element exists
            var pos = el.offsetTop;
            positions.push(pos);
        } else {
            console.warn('Element with id ' + id + ' not found');
        }
    }


    //add event listener for scroll
    window.addEventListener('scroll', function() {

        //get the current scroll position
        var scrollPos = window.scrollY;

        //offset by 100px
        scrollPos += 100;

        //loop through the positions and find the one that is closest to the scroll position
        var closest = 0;
        for (var i = 0; i < positions.length; i++) {
            if (positions[i] < scrollPos) {
                closest = i;
            }
        }

        //remove active class from all links
        removeActive();

        //add active class to the closest link
        nav.querySelector(listElem + ' a[href="' + hrefs[closest] + '"]').classList.add('active');

        //if we're at the bottom of the page, add active class to the last link
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight) {
            removeActive();
            nav.querySelector(listElem + ' a[href="' + hrefs[hrefs.length - 1] + '"]').classList.add('active');
        }

    });

    //floating panel helpers
    //if .autoss-floatcontainer exists...
    var floatContainer = document.querySelector('.autoss-floatcontainer');

    if (floatContainer) {

        let floatToggler = document.querySelector('.autoss-float-toggle');

        floatToggler.style.top = floatPanelToggleOffsetTop;

        const autoCollapseWidth = nav.getAttribute('data-collapsewidth');
        //if it's 0px, we always collapse it
        if (autoCollapseWidth == 0) {
            floatContainer.classList.add('autoss-collapsed');
        }



        //check if we need to collapse it on page load
        if (window.innerWidth <= autoCollapseWidth || autoCollapseWidth == 0) {
            floatContainer.classList.add('autoss-collapsed');
            //show toggle
            floatToggler.classList.remove('autoss-togglehidden');
        }
        else{
            floatToggler.classList.add('autoss-togglehidden');
        }

        if(autoCollapseWidth != 0) {
            //if user resizes window, check if we need to collapse it
            window.addEventListener('resize', function() {
                if (window.innerWidth <= autoCollapseWidth) {
                    floatContainer.classList.add('autoss-collapsed');
                    //show toggle
                    floatToggler.classList.remove('autoss-togglehidden');
                } else {
                    floatContainer.classList.remove('autoss-collapsed');
                    //hide toggle
                    floatToggler.classList.add('autoss-togglehidden');
                }
            });
        }

        //someone clicks toggle
        floatToggler.addEventListener('click', function() {
            floatContainer.classList.toggle('autoss-collapsed');
        }
        );

    }

    attemptForceSticky();

});

function attemptForceSticky(){
    const autoss = document.querySelector('#autoScrollSpyContainer');
    //validate data-sticky="true"
    const sticky = autoss.getAttribute('data-sticky');
    if(sticky != 'true'){
        return;
    }
    //get from data-parentlevel
    const parentLevel = autoss.getAttribute('data-sticky-parent-level');
    //find the parent element parentLevels up from autoss
    let parent = autoss;
    for(let i = 0; i < parentLevel; i++){
        parent = parent.parentElement;
    }

    //set style of parent to sticky
    parent.style.position = 'sticky';
    parent.style.top = '0';
    parent.style.zIndex = '1000';

}


//handle offset scrollOffsetTop attribute given from params
//whenever a local link is clicked, we need to scroll to the right position
//joomla provides scrollOffsetTop as const so we can use that
//add event listener to all .autoss-local-link  (they are a elems)

if(scrollOffsetTop != 0){
    const localLinks = document.querySelectorAll('.autoss-local-link');
    for (var i = 0; i < localLinks.length; i++) {
        localLinks[i].addEventListener('click', function(e) {
            e.preventDefault();
            console.log('clicked'   + this.getAttribute('href'));
            var href = this.getAttribute('href');
            var id = href.replace('#', '');
            var el = document.getElementById(id);
            var pos = el.offsetTop - scrollOffsetTop;
            window.scrollTo({
                top: pos,
                behavior: 'smooth'
            });
        });
    }
}



