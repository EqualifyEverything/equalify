<main class="form-signin bg-light justify-content-center align-items-center text-center">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" method="post">
        <div class="d-flex pb-4 text-success justify-content-center align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi me-2 bi-patch-check-fill" viewBox="0 0 16 16">
                <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
            </svg>
        </div>
        <h1 class="h3 mb-3 fw-normal">Please log in.</h1>
        <div class="form-floating">
            <input type="email" name="email" class="form-control" id="email" placeholder="email@address.com" required>
            <label for="mail">Email address</label>
        </div>
        <div class="form-floating">
            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
            <label for="password">Password</label>
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Sign In</button>
        <p class="mt-5 mb-3 text-muted"><strong>Login problems?</strong> <br>Email: <a class="link-secondary" href="mailto:blake@edupack.dev">blake@edupack.dev</a></p>
    </form>
</main>

