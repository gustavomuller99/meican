"""
Usage:
  python get_circuit_state.py <rpc_url> <contract_address> <external_id>
"""
import sys
from web3 import Web3

def main():
    if len(sys.argv) != 4:
        print(__doc__)
        sys.exit(1)

    rpc_url, contract_address, external_id = sys.argv[1:]

    w3 = Web3(Web3.HTTPProvider(rpc_url))

    abi = [
        {
            "name": "getCircuitState",
            "type": "function",
            "inputs": [{"name": "externalId", "type": "string"}],
            "outputs": [
                {"name": "", "type": "tuple", "components": [
                    {"name": "userName",        "type": "string"},
                    {"name": "reservationName", "type": "string"},
                    {"name": "bandwidth",       "type": "string"},
                    {"name": "status",          "type": "string"},
                    {"name": "resourcesStatus", "type": "string"},
                    {"name": "dataplaneStatus", "type": "string"},
                    {"name": "authStatus",      "type": "string"},
                    {"name": "start",           "type": "string"},
                    {"name": "finish",          "type": "string"},
                ]},
                {"name": "", "type": "tuple", "components": [
                    {"name": "domain", "type": "string"},
                    {"name": "status", "type": "string"},
                ]},
                {"name": "", "type": "tuple", "components": [
                    {"name": "eventType", "type": "string"},
                    {"name": "status",    "type": "string"},
                ]},
            ],
            "stateMutability": "view",
        }
    ]

    contract = w3.eth.contract(address=Web3.to_checksum_address(contract_address), abi=abi)
    conn_status, conn_auth, conn_circuit = contract.functions.getCircuitState(external_id).call()

    print("ConnectionStatus:")
    print(f"  userName:        {conn_status[0]}")
    print(f"  reservationName: {conn_status[1]}")
    print(f"  bandwidth:       {conn_status[2]}")
    print(f"  status:          {conn_status[3]}")
    print(f"  resourcesStatus: {conn_status[4]}")
    print(f"  dataplaneStatus: {conn_status[5]}")
    print(f"  authStatus:      {conn_status[6]}")
    print(f"  start:           {conn_status[7]}")
    print(f"  finish:          {conn_status[8]}")

    print("ConnectionAuth:")
    print(f"  domain: {conn_auth[0]}")
    print(f"  status: {conn_auth[1]}")

    print("ConnectionCircuit:")
    print(f"  eventType: {conn_circuit[0]}")
    print(f"  status:    {conn_circuit[1]}")

if __name__ == "__main__":
    main()
