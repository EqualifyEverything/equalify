This page is intended for **Administrators** responsible for managing system permissions and workspace security. This section covers the stages of user management: adding new users, changing permissions, and removing accounts.<br>

For Administrators, it helps to think of the Invites and Users sections as two different stages:
- **The Invites List:** People who have been asked to join but haven't logged in yet.
- **The Users List:** People who have already joined and are currently using Equalify.
## Invites
### Inviting users
1. Navigate to Account > Invites.
1. Enter the email address of the person you want to invite.
1. Click Invite.
    - A success banner will confirm the action.
    - The user will receive an email with a login link.<br>

<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Invite%20Sent.png" alt="Confirmation notification banner stating Invite sent successfully!" width="500"/><br>
Once the invited user logs in (via SSO or another authentication method), Equalify automatically adds them to your workspace.
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Invites.png" alt="Invites table displaying email addresses, dates added, and options to delete each entry." width="500"/><br>
```important
⚠️Important: If your Equalify instance uses Single Sign-On (SSO), invited users must have an email address from an authorized workspace domain (e.g., @uic.edu). If not, you will receive an error.
```

<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Invite%20Error%20Domain.png" alt="Error pop up: app.equalify.uic.edu says: Failed to send invite. Email domain not authorized for invitation. Ok button." width="500"/><br>
```important
⚠️Important: If the user is already in the system, a duplicate invite will not be sent.
```
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Invite%20Error%20Duplicate%20Email.png" alt="Error notification banner stating: Failed to send invite: User already exists for this email address." width="500"/><br>
### Deleting pending invites
User invites can be removed for any reason before a user logs in:
1. Navigate to Account > Invites.
1. Find the email address you want to remove.
1. Click Delete.
    - A success banner will confirm the action.
    - The link in the invitation email will no longer work.
  
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Invite%20Deleted.png" alt="Confirmation notification banner stating Invite deleted" width="500"/>

## Users
### Changing user roles
You can control what a user can see or do by changing their role:
1. Find the person’s name in the Users list.
1. Click the dropdown menu in the Type column.
1. Select a new role:
    - **Member:** Can view and contribute to content but cannot manage other users.
    - **Admin:** Has full access, including the ability to invite or remove others.
1. The change is immediate, and a confirmation will appear. No "Save" button is required. The user will see the change the next time they login to Equalify.<br>

<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Users%20Type%20Dropdown.png" alt="Users table showing a Type dropdown menu with Member and Admin options selected for a user row." width="500"/><br>
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Users%20Type%20Confirmation.png" alt="Confirmation notification banner stating User type updated successfully!" width="500"/>

### Verifying join dates
If you need to confirm when a person first accessed the system, check the Created At column. This shows the exact date they accepted their invite and established their account.<br>
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/9c29d5f90e395bc43c0563f4e4fcca90557aebbe/user/User%20Guide%20Images/Sec%205_Users.png" alt="Users table with columns for Name, Email, Type dropdown, Created At date, and a Remove action button for each entry." width="500"/>

### Removing a user
If a person no longer needs access to the system:
1. Find the user in the list.
1. Click the Remove button (in red).
1. A success banner will confirm the action.
    - The user will be logged out and will no longer be able to access the system.



