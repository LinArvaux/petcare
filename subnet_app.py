import tkinter as tk
from tkinter import ttk, messagebox
import math
import ipaddress

def calculate_vlsm():
    # Clear previous results from the table
    for item in tree.get_children():
        tree.delete(item)
        
    ip_input = entry_ip.get().strip()
    hosts_input = entry_hosts.get().strip()
    
    if not ip_input or not hosts_input:
        messagebox.showwarning("Input Error", "Please provide both a starting IP and host requirements.")
        return

    try:
        # Parse the starting IP (ignores CIDR if accidentally included)
        base_ip = ipaddress.IPv4Address(ip_input.split('/')[0])
        
        # Parse and sort the requested hosts in descending order
        host_requests = [int(x.strip()) for x in hosts_input.split(',')]
        host_requests.sort(reverse=True)
        
        current_ip = base_ip
        
        for req in host_requests:
            if req <= 0:
                continue
                
            # 1. Calculate required host bits
            host_bits = math.ceil(math.log2(req + 2))
            
            # 2. Determine CIDR prefix and allocated hosts
            prefix = 32 - host_bits
            allocated_hosts = (2 ** host_bits) - 2
            
            # 3. Define the network
            network = ipaddress.IPv4Network(f"{current_ip}/{prefix}", strict=False)
            
            # 4. Find usable host range
            hosts = list(network.hosts())
            usable_range = f"{hosts[0]} - {hosts[-1]}" if hosts else "N/A"
            
            # 5. Insert row into the table
            tree.insert("", "end", values=(
                req,
                allocated_hosts,
                str(network.network_address),
                f"/{prefix}",
                str(network.netmask),
                usable_range,
                str(network.broadcast_address)
            ))
            
            # 6. Set next IP
            current_ip = network.broadcast_address + 1
            
    except ValueError:
        messagebox.showerror("Input Error", "Host requirements must be numbers separated by commas (e.g., 600, 500, 400).")
    except ipaddress.AddressValueError:
        messagebox.showerror("IP Error", "Invalid starting IP address or address space exceeded.")
    except Exception as e:
        messagebox.showerror("Error", f"An unexpected error occurred:\n{e}")

# Set up the main window
root = tk.Tk()
root.title("VLSM Calculator")
root.geometry("850x400")

# Styling
style = ttk.Style()
style.configure("Treeview.Heading", font=("Arial", 10, "bold"))
style.configure("Treeview", rowheight=25)

# Input Frame
frame_input = ttk.Frame(root, padding="10")
frame_input.pack(fill="x")

ttk.Label(frame_input, text="Starting IP Address:").grid(row=0, column=0, padx=5, pady=5, sticky="e")
entry_ip = ttk.Entry(frame_input, width=20)
entry_ip.grid(row=0, column=1, padx=5, pady=5, sticky="w")
entry_ip.insert(0, "172.168.0.0")

ttk.Label(frame_input, text="Requested Hosts (comma-separated):").grid(row=1, column=0, padx=5, pady=5, sticky="e")
entry_hosts = ttk.Entry(frame_input, width=50)
entry_hosts.grid(row=1, column=1, padx=5, pady=5, sticky="w")
entry_hosts.insert(0, "600, 500, 400, 300, 200, 100, 50, 25, 2, 2, 2, 2")

btn_calc = ttk.Button(frame_input, text="Calculate VLSM", command=calculate_vlsm)
btn_calc.grid(row=0, column=2, rowspan=2, padx=15, pady=5, sticky="nsew")

root.bind('<Return>', lambda event: calculate_vlsm())

# Table (Treeview) Frame
frame_table = ttk.Frame(root, padding="10")
frame_table.pack(fill="both", expand=True)

# Define Columns
columns = ("Req", "Allocated", "Network", "CIDR", "Mask", "Range", "Broadcast")
tree = ttk.Treeview(frame_table, columns=columns, show="headings", selectmode="none")

# Define Headings and Column Widths
tree.heading("Req", text="Req Hosts")
tree.column("Req", width=80, anchor="center")

tree.heading("Allocated", text="Allocated")
tree.column("Allocated", width=80, anchor="center")

tree.heading("Network", text="Network Address")
tree.column("Network", width=120, anchor="center")

tree.heading("CIDR", text="CIDR")
tree.column("CIDR", width=50, anchor="center")

tree.heading("Mask", text="Subnet Mask")
tree.column("Mask", width=120, anchor="center")

tree.heading("Range", text="Usable Range")
tree.column("Range", width=230, anchor="center")

tree.heading("Broadcast", text="Broadcast")
tree.column("Broadcast", width=120, anchor="center")

# Add Scrollbar
scrollbar = ttk.Scrollbar(frame_table, orient="vertical", command=tree.yview)
tree.configure(yscrollcommand=scrollbar.set)
scrollbar.pack(side="right", fill="y")
tree.pack(fill="both", expand=True)

# Start Application
root.mainloop()