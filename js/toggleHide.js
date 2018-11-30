function toggleHide(){

    var toggleElts = document.getElementsByClassName("toggle");

    for (var elt in toggleElts) {

        if (toggleElts[elt].style.getPropertyValue("display") === "none") {


            toggleElts[elt].style.setProperty("display", "", null);

        } else {

            toggleElts[elt].style.setProperty("display", "none", null);

        }
    }
}