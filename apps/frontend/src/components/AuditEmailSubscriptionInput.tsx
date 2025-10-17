import { useCallback, useEffect, useState } from "react";
import { v4 as uuidv4 } from "uuid";

export interface EmailSubscriptionList {
  emails: EmailSubscriptionEmail[];
}
interface EmailSubscriptionEmail {
  id: string;
  email: string;
  frequency: string; // daily|weekly|monthly
  lastSent: string; // UTC date string
}

const frequencyOpts = ["Daily", "Weekly", "Monthly"];

interface ChildProps {
  initialValue: EmailSubscriptionList;
  onValueChange: (newValue: EmailSubscriptionList) => void; // Callback function prop
}

// main component
export const AuditEmailSubscriptionInput: React.FC<ChildProps> = ({
  initialValue,
  onValueChange,
}) => {
  const [emails, setEmails] = useState(initialValue.emails);

  const handleUpdateField = useCallback(
    (idToUpdate: string, field: string, value: string) => {
      setEmails((prevEmails) =>
        prevEmails.map((entry) =>
          entry.id === idToUpdate ? { ...entry, [field]: value } : entry
        )
      );
    },
    []
  );

  const handleAddEmail = () => {
    setEmails((prevEmails) => [
      ...prevEmails,
      { id: uuidv4(), email: "user@uic.edu", frequency: "Weekly", lastSent: "" },
    ]);
  };

  const handleRemoveEmail = useCallback(
    (idToRemove: string) => {
      if (emails.length > 1) {
        setEmails((prevEmails) =>
          prevEmails.filter((email) => email.id !== idToRemove)
        );
      }
    },
    [emails.length]
  );

  // whenever we change values, update the parent data
  useEffect(()=>{
    console.log("Updating...", emails)
    onValueChange({ emails: emails })
  },[emails]);

  return (
    <>
      {emails.map((entry) => (
        <EmailInputRow
          key={entry.id}
          entry={entry}
          onChange={handleUpdateField}
          onRemove={handleRemoveEmail}
        />
      ))}
      <button onClick={handleAddEmail} type="button">
        Add Another Email
      </button>
    </>
  );
};

// component for individual row
interface EmailInputRowProps {
  entry: EmailSubscriptionEmail;
  onChange: (id: string, field: string, value: string) => void;
  onRemove: (id: string) => void;
}

const EmailInputRow: React.FC<EmailInputRowProps> = ({
  entry,
  onChange,
  onRemove,
}) => {
  const { id, email, frequency } = entry;

  const handleChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      const { name, value } = e.target;
      onChange(id, name, value);
    },
    [id, onChange]
  );

  const handleRemoveClick = useCallback(() => {
    onRemove(id);
  }, [id, onRemove]);

  return (
    <div className="inline-flex">
      <label htmlFor={`email-${id}`}>Email</label>
      <input
        id={`email-${id}`}
        name="email"
        type="email"
        placeholder="user@example.com"
        value={email}
        onChange={handleChange}
      />

      <label htmlFor={`frequency-${id}`}>Frequency</label>
      <select
        id={`frequency-${id}`}
        name="frequency"
        value={frequency}
        onChange={handleChange}
      >
        {frequencyOpts.map((option) => (
          <option key={option} value={option}>
            {option}
          </option>
        ))}
      </select>

        <button
          onClick={handleRemoveClick}
          type="button"
          aria-label={`Remove email ${email}`}
        >
          Remove
        </button>
    </div>
  );
};
