import { Table } from "@tanstack/react-table";
import style from "./BlockersTableColumnToggle.module.scss";
import * as Popover from "@radix-ui/react-popover";
import { Blocker } from "./BlockersTable";
import { MdOutlineClose } from "react-icons/md";
import { LuSettings2 } from "react-icons/lu";

interface BlockersTableColumnToggleProps {
  table: Table<Blocker>
  /** Matches the trigger to its neighbouring table actions (Scan History, Download) */
  triggerClassName?: string
}
interface StringMap {
  [key: string]: string;
}
const labelMap: StringMap = {
  'type': "Type",
  'short_id': "ID",
  'url': "URL",
  'messages': "Description",
  'content': "Code",
  'tags': "Accessibility Standards",
  'categories': "Rule",
  'id': "Ignore"
}

export const BlockersTableColumnToggle = ({ table, triggerClassName }: BlockersTableColumnToggleProps) => {

  return (
    <div className={style["BlockersTableColumnToggle"]}>
      <Popover.Root>
        <Popover.Trigger className={triggerClassName}>
          <LuSettings2 aria-hidden="true" />
          <span>Columns</span>
        </Popover.Trigger>
        <Popover.Portal>
          <Popover.Content sideOffset={5} className={style["popoverContent"]}>
            <fieldset className={style["popoverFieldset"]}>
              <legend className="font-small">Show/Hide Columns</legend>
              {table.getAllColumns().map((column) => (
                <label key={column.id}>
                  <input
                    checked={column.getIsVisible()}
                    onChange={column.getToggleVisibilityHandler()}
                    type="checkbox"
                  />
                  {labelMap[column.id]}
                </label>
              ))}
            </fieldset>
            <Popover.Close aria-label="Close" className={style["popoverClose"]}>
              <MdOutlineClose />
            </Popover.Close>
            <Popover.Arrow className={style["popoverArrow"]} />
          </Popover.Content>
        </Popover.Portal>
      </Popover.Root>

    </div >
  );
};
