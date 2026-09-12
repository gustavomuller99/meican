"""
Usage:
  python set_connection_circuit.py <rpc_url> <contract_address> <private_key> \
    <external_id> <event_type> <status>
"""
import sys
from web3 import Web3
from web3.exceptions import Web3RPCError

def main():
    if len(sys.argv) != 7:
        print(__doc__)
        sys.exit(1)

    rpc_url, contract_address, private_key, external_id, event_type, status = sys.argv[1:]

    w3 = Web3(Web3.HTTPProvider(rpc_url))
    account = w3.eth.account.from_key(private_key)

    abi = [
        {
            "name": "setConnectionCircuit",
            "type": "function",
            "inputs": [
                {"name": "externalId", "type": "string"},
                {"name": "eventType",  "type": "string"},
                {"name": "status",     "type": "string"},
            ],
            "outputs": [],
            "stateMutability": "nonpayable",
        }
    ]

    contract = w3.eth.contract(address=Web3.to_checksum_address(contract_address), abi=abi)

    tx = contract.functions.setConnectionCircuit(
        external_id, event_type, status
    ).build_transaction({
        "from":  account.address,
        "nonce": w3.eth.get_transaction_count(account.address),
        "gas":   500000,
    })

    signed = account.sign_transaction(tx)
    try:
        tx_hash = w3.eth.send_raw_transaction(signed.raw_transaction)
        receipt = w3.eth.wait_for_transaction_receipt(tx_hash)
        print(f"tx:     {tx_hash.hex()}")
        print(f"status: {'ok' if receipt.status == 1 else 'FAILED'}")
    except Web3RPCError as e:
        reason = e.args[0].get("message", str(e)) if isinstance(e.args[0], dict) else str(e)
        print(f"error:  {reason}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
