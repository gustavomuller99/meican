"""
Usage:
  python set_connection_status.py <rpc_url> <contract_address> <private_key> \
    <external_id> <user_name> <reservation_name> <bandwidth> <status> \
    <resources_status> <dataplane_status> <auth_status> <start> <finish>
"""
import sys
from web3 import Web3
from web3.exceptions import Web3RPCError

def main():
    if len(sys.argv) != 14:
        print(__doc__)
        sys.exit(1)

    rpc_url, contract_address, private_key, \
        external_id, user_name, reservation_name, bandwidth, status, \
        resources_status, dataplane_status, auth_status, start, finish = sys.argv[1:]

    w3 = Web3(Web3.HTTPProvider(rpc_url))
    account = w3.eth.account.from_key(private_key)

    abi = [
        {
            "name": "setConnectionStatus",
            "type": "function",
            "inputs": [
                {"name": "externalId", "type": "string"},
                {"name": "data", "type": "tuple", "components": [
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
            ],
            "outputs": [],
            "stateMutability": "nonpayable",
        }
    ]

    contract = w3.eth.contract(address=Web3.to_checksum_address(contract_address), abi=abi)

    tx = contract.functions.setConnectionStatus(
        external_id,
        (user_name, reservation_name, bandwidth, status,
         resources_status, dataplane_status, auth_status, start, finish)
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
