# Dev Notes
### Why is `throw new Exception` everywhere?
 Users shouldn't be able to take care of business with minimal roadblocks. That means even errors like "password is incorrect" are UX errors. 