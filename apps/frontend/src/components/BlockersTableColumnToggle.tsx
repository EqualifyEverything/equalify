import { Table } from "@tanstack/react-table";
import style from "./BlockersTableColumnToggle.module.scss";
import * as Popover from "@radix-ui/react-popover";
import { Blocker } from "./BlockersTable";
import { MdOutlineClose } from "react-icons/md";
import { LuSettings2 } from "react-icons/lu";
import { StyledButton } from "./StyledButton";

interface BlockersTableColumnToggleProps {
  table: Table<Blocker>
}
interface StringMap {
  [key: string]: string;
}
const labelMap: StringMap = {
  'type': "Type",
  'url': "URL",
  'messages': "Issue",
  'content': "Code",
  'tags': "Tags",
  'categories': "Category",
  'id': "Ignore"
}

export const BlockersTableColumnToggle = ({ table }: BlockersTableColumnToggleProps) => {

  return (
    <div className={style["BlockersTableColumnToggle"]}>
      <Popover.Root>
        <Popover.Trigger asChild className={style["blockersTableToggleButton"]}>
          <StyledButton
            variant="naked"
            className="large"
            icon={<LuSettings2 />}
            label={"Show/Hide Table Columns"}
            showLabel={false}
            onClick={() => { }}
          />
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
