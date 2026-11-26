function toggleMenu(){

    document.querySelector(".sidebar").classList.toggle("show");
}


/* ================= SHOW PASSWORD ================= */

const showBtn =
document.getElementById("showBtn");

const password =
document.getElementById("password");

if(showBtn && password){

    showBtn.addEventListener("click", function(){

        if(password.type === "password"){

            password.type = "text";

            showBtn.innerHTML =
            '<i class="fa-solid fa-eye-slash"></i>';

        }else{

            password.type = "password";

            showBtn.innerHTML =
            '<i class="fa-solid fa-eye"></i>';
        }
    });
}