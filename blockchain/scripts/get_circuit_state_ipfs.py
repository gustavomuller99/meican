"""
Usage:
  python get_circuit_state_ipfs.py <rpc_url> <contract_address> <external_id>
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
            "name": "getCircuitStateIPFS",
            "type": "function",
            "inputs": [{"name": "externalId", "type": "string"}],
            "outputs": [
                {"name": "", "type": "string"},
                {"name": "", "type": "string"},
                {"name": "", "type": "string"},
            ],
            "stateMutability": "view",
        }
    ]

    contract = w3.eth.contract(address=Web3.to_checksum_address(contract_address), abi=abi)
    status_cid, auth_cid, circuit_cid = contract.functions.getCircuitStateIPFS(external_id).call()

    print("ConnectionStatus CID:  ", status_cid  or "(empty)")
    print("ConnectionAuth CID:    ", auth_cid    or "(empty)")
    print("ConnectionCircuit CID: ", circuit_cid or "(empty)")

if __name__ == "__main__":
    main()
