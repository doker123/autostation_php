function initConditionalVisibility(
  selectRef,
  targetRef,
  triggerValue,
  displayMode = "block",
) {
  const select =
    typeof selectRef === "string"
      ? document.querySelector(selectRef)
      : selectRef;
  const target =
    typeof targetRef === "string"
      ? document.querySelector(targetRef)
      : targetRef;

  if (!select || !target) {
    return;
  }

  const toggle = () => {
    const isMatch = select.value.trim() === triggerValue;
    target.style.display = isMatch ? displayMode : "none";
  };

  select.addEventListener("change", toggle);
  toggle();
}

document.addEventListener("DOMContentLoaded", () => {
  initConditionalVisibility(
    "#select_tariff",
    ".input-tariff",
    "create_tariff",
    "block",
  );

  initConditionalVisibility("#spot", ".input-spot", "create_spot", "block");
});
