# User Roles and Permissions

Equalify uses a role-based system to manage what users can do within the platform. This guide explains the available roles and how access works.

## Roles

### Admin
The **Admin** role is automatically assigned to the first user who logs in to a new Equalify instance. Admins have full access to all platform features, including user management.

Admin capabilities:
- Create, edit, and delete audits
- View all scan results and blockers
- Invite new users to the instance
- Manage users
- Access activity logs
- Configure audit schedules and notifications

### User
The **User** role is the default role for all invited users. Users have access to the core scanning and reporting features.

User capabilities:
- Create, edit, and delete their own audits
- View scan results and blockers for audits shared within the instance
- Invite new users to the instance
- Access activity logs
- Configure audit schedules and notifications

## Shared Access Within Your Instance

All users on an Equalify instance share access to each other's audits. There is no separate "teams" feature — your Equalify instance is your shared workspace.

- Audits created by any user are visible to all other users on the same instance.
- Invited users are automatically added to your instance when they log in.

## Inviting Users

Any authenticated user can invite new members to join the instance:

1. Navigate to **Account** from the main menu.
2. Enter the email address of the person you want to invite.
3. Click **Invite**.
4. The invited user will receive an email with a link to log in.

Once the invited user logs in (via SSO or other authentication), they are automatically added to your instance.

> **Note**: If your Equalify instance uses SSO, invited users must have an email address from an authorized domain (e.g., `@uic.edu`).

## Data Access

Equalify enforces data isolation so that users only see what they should:

- **Your audits**: You can always see audits you created.
- **Instance audits**: You can see audits created by anyone on your instance.
- **Activity logs**: Logs reflect actions taken across your instance's audits.

You will not see audits or data from other Equalify instances.
