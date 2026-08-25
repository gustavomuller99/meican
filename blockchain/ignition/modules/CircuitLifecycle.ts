import { buildModule } from "@nomicfoundation/hardhat-ignition/modules";

export default buildModule("CircuitLifecycleModule", (m) => {
  const audit = m.contract("CircuitLifecycle");
  return { audit };
});