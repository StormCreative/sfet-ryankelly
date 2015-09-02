
        <div id="main-head">
            <h1 class="main-heading">Storm Front-End test</h1>
            
            <button id="signup-btn">Signup!</button>
        </div>
        
        <form id="signup-box" action="signup/process" method="post">
                <span id="popup-close">X</span>
                
                <div class="form-field">
                <label for="name">Name:</label>
                <input type="text" name="name" id="input-text-name" class="input-text-field" />
                </div>
                <div class="form-field">
                <label for="email">Email:</label>
                <input type="email" name="email" id="input-text-email" class="input-text-field" />
                </div>
                <div id="message-box">
                
                </div>
                <input type="submit" id="submit-btn" value="Submit"/>
        </form>
